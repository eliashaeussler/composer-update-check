<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020-2026 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\ComposerUpdateCheck;

use Composer\IO;
use EliasHaeussler\TaskRunner;
use Symfony\Component\Console;

use function array_fill_keys;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;

/**
 * UpdateChecker.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class UpdateChecker
{
    private TaskRunner\TaskRunner $taskRunner;

    public function __construct(
        private \Composer\Composer $composer,
        private Composer\Installer $installer,
        private IO\IOInterface $io,
        private Security\SecurityScanner $securityScanner,
        private Reporter\ReporterFactory $reporterFactory,
    ) {
        $this->taskRunner = new TaskRunner\TaskRunner($this->io);
    }

    /**
     * @throws Exception\ComposerInstallFailed
     * @throws Exception\ComposerUpdateFailed
     * @throws Exception\PackagistResponseHasErrors
     * @throws Exception\ReporterIsNotSupported
     * @throws Exception\ReporterOptionsAreInvalid
     * @throws Exception\UnableToFetchSecurityAdvisories
     */
    public function run(Configuration\ComposerUpdateCheckConfig $config): Entity\Result\UpdateCheckResult
    {
        $this->validateReporters($config->getReporters());

        // Run update check
        [$packages, $excludedPackages] = $this->resolvePackagesForUpdateCheck($config);
        $result = $this->runUpdateCheck($packages, $excludedPackages);

        // Overlay security scan
        if ($config->shouldPerformSecurityScan() && [] !== $result->getOutdatedPackages()) {
            $this->taskRunner->run(
                '🚨 Looking up security advisories',
                fn () => $this->securityScanner->scanAndOverlayResult($result),
                Console\Output\OutputInterface::VERBOSITY_VERBOSE,
            );
        }

        // Dispatch event
        $this->dispatchPostUpdateCheckEvent($result);

        // Report update check result
        foreach ($config->getReporters() as $name => $options) {
            $reporter = $this->reporterFactory->make($name);
            $reporter->report($result, $options);
        }

        return $result;
    }

    /**
     * @param list<Entity\Package\Package>         $packages
     * @param list<Entity\Package\ExcludedPackage> $excludedPackages
     *
     * @throws Exception\ComposerInstallFailed
     * @throws Exception\ComposerUpdateFailed
     */
    private function runUpdateCheck(array $packages, array $excludedPackages): Entity\Result\UpdateCheckResult
    {
        // Early return if no packages are listed for update check
        if ([] === $packages) {
            return new Entity\Result\UpdateCheckResult([], $excludedPackages, $this->lookupRootPackage());
        }

        // Ensure dependencies are installed
        $this->installDependencies();

        $result = $this->taskRunner->run(
            '⏳ Checking for outdated packages',
            function (TaskRunner\RunnerContext $context) use ($packages) {
                $io = new IO\BufferIO();

                // Run Composer installer
                $result = $this->installer->runUpdate($packages, $io);

                // Handle installer failures
                if (!$result->isSuccessful()) {
                    $context->output->write($io->getOutput());

                    throw new Exception\ComposerUpdateFailed($result->getExitCode());
                }

                return $result;
            },
            Console\Output\OutputInterface::VERBOSITY_VERBOSE,
        );

        return new Entity\Result\UpdateCheckResult(
            $result->getOutdatedPackages(),
            $excludedPackages,
            $this->lookupRootPackage(),
        );
    }

    /**
     * @throws Exception\ComposerInstallFailed
     */
    private function installDependencies(): void
    {
        // Run Composer installer
        $io = new IO\BufferIO();
        $exitCode = $this->installer->runInstall($io);

        // Handle installer failures
        if ($exitCode > 0) {
            $this->io->writeError($io->getOutput());

            throw new Exception\ComposerInstallFailed($exitCode);
        }
    }

    /**
     * @return array{list<Entity\Package\Package>, list<Entity\Package\ExcludedPackage>}
     */
    private function resolvePackagesForUpdateCheck(Configuration\ComposerUpdateCheckConfig $config): array
    {
        return $this->taskRunner->run(
            '📦 Resolving packages',
            function (TaskRunner\RunnerContext $context) use ($config) {
                $rootPackage = $this->composer->getPackage();
                /** @var array<non-empty-string> $requiredPackages */
                $requiredPackages = array_keys($rootPackage->getRequires());
                /** @var array<non-empty-string> $requiredDevPackages */
                $requiredDevPackages = array_keys($rootPackage->getDevRequires());
                $excludedPackages = [];

                // Handle dev-packages
                if ($config->areDevPackagesIncluded()) {
                    $requiredPackages = array_merge($requiredPackages, $requiredDevPackages);
                } else {
                    $excludedPackages = array_fill_keys($requiredDevPackages, null);

                    $context->output->writeln('🚫 Skipped dev-requirements', Console\Output\OutputInterface::VERBOSITY_VERBOSE);
                }

                // Remove packages by exclude patterns
                $excludedPackages = array_merge(
                    $excludedPackages,
                    $this->removeByExcludePatterns($requiredPackages, $config->getExcludePatterns(), $context->output),
                );

                return [
                    array_values($this->mapPackageNamesToPackage($requiredPackages)),
                    $this->mapExcludedPackages($excludedPackages),
                ];
            },
            Console\Output\OutputInterface::VERBOSITY_VERBOSE,
        );
    }

    /**
     * @param array<non-empty-string>                           $packages
     * @param list<Configuration\Options\PackageExcludePattern> $excludePatterns
     *
     * @return array<non-empty-string, Configuration\Options\PackageExcludePattern>
     */
    private function removeByExcludePatterns(
        array &$packages,
        array $excludePatterns,
        Console\Output\OutputInterface $output,
    ): array {
        $excludedPackages = [];

        $packages = array_filter(
            $packages,
            static function (string $package) use (&$excludedPackages, $excludePatterns, $output) {
                foreach ($excludePatterns as $excludePattern) {
                    if ($excludePattern->matches($package)) {
                        $excludedPackages[$package] = $excludePattern;

                        $output->writeln(
                            sprintf('🚫 Skipped <info>%s</info>', $package),
                            Console\Output\OutputInterface::VERBOSITY_VERBOSE,
                        );

                        return false;
                    }
                }

                return true;
            },
        );

        return $excludedPackages;
    }

    /**
     * @param array<string, array<string, mixed>> $reporters
     *
     * @throws Exception\ReporterIsNotSupported
     */
    private function validateReporters(array $reporters): void
    {
        foreach ($reporters as $name => $options) {
            // Will throw an exception if reporter is not supported
            $reporter = $this->reporterFactory->make($name);
            // Will throw an exception if reporter options are invalid
            $reporter->validateOptions($options);
        }
    }

    /**
     * @param array<non-empty-string> $packageNames
     *
     * @return array<Entity\Package\Package>
     */
    private function mapPackageNamesToPackage(array $packageNames): array
    {
        return array_map(
            static fn (string $packageName) => new Entity\Package\InstalledPackage($packageName),
            $packageNames,
        );
    }

    /**
     * @param array<non-empty-string, Configuration\Options\PackageExcludePattern|null> $excludedPackages
     *
     * @return list<Entity\Package\ExcludedPackage>
     */
    private function mapExcludedPackages(array $excludedPackages): array
    {
        $packages = [];

        foreach ($excludedPackages as $packageName => $excludePattern) {
            $excludeReason = null === $excludePattern
                ? Entity\Package\ExcludeReason::NoDev
                : Entity\Package\ExcludeReason::Pattern
            ;

            $packages[] = new Entity\Package\ExcludedPackage($packageName, $excludeReason, $excludePattern);
        }

        return $packages;
    }

    private function dispatchPostUpdateCheckEvent(Entity\Result\UpdateCheckResult $result): void
    {
        $event = new Event\PostUpdateCheckEvent($result);

        $this->composer->getEventDispatcher()->dispatch($event->getName(), $event);
    }

    private function lookupRootPackage(): ?Entity\Package\InstalledPackage
    {
        $rootPackageName = $this->composer->getPackage()->getName();

        if ('__root__' === $rootPackageName || '' === $rootPackageName) {
            return null;
        }

        return new Entity\Package\InstalledPackage($rootPackageName);
    }
}
