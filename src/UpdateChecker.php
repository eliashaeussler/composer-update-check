<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2023 Elias Häußler <elias@haeussler.dev>
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

use Composer\Composer;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use EliasHaeussler\ComposerUpdateCheck\Composer\CommandResultParser;
use EliasHaeussler\ComposerUpdateCheck\Composer\ComposerInstaller;
use EliasHaeussler\ComposerUpdateCheck\Configuration\ComposerUpdateCheckConfig;
use EliasHaeussler\ComposerUpdateCheck\Configuration\Options\PackageExcludePattern;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\InstalledPackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\Package;
use EliasHaeussler\ComposerUpdateCheck\Entity\Result\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;
use EliasHaeussler\ComposerUpdateCheck\Reporter\ReporterFactory;
use EliasHaeussler\ComposerUpdateCheck\Security\SecurityScanner;

use function array_map;
use function array_merge;

/**
 * UpdateChecker.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateChecker
{
    public function __construct(
        private readonly CommandResultParser $commandResultParser,
        private readonly Composer $composer,
        private readonly ComposerInstaller $installer,
        private readonly IOInterface $io,
        private readonly SecurityScanner $securityScanner,
        private readonly ReporterFactory $reporterFactory,
    ) {}

    /**
     * @throws Exception\ComposerInstallFailed
     * @throws Exception\ComposerUpdateFailed
     * @throws Exception\PackagistResponseHasErrors
     * @throws Exception\ReporterIsNotSupported
     * @throws Exception\UnableToFetchSecurityAdvisories
     */
    public function run(ComposerUpdateCheckConfig $config): UpdateCheckResult
    {
        $reporters = [];

        // Resolve reporters
        foreach ($config->getReporters() as $name => $options) {
            $reporters[] = $this->reporterFactory->make($name, $options);
        }

        // Run update check
        [$packages, $excludedPackages] = $this->resolvePackagesForUpdateCheck($config);
        $result = $this->runUpdateCheck($packages, $excludedPackages);

        // Overlay security scan
        if ($config->shouldPerformSecurityScan() && [] !== $result->getOutdatedPackages()) {
            $this->io->writeError('🚨 Checking for insecure packages...', true, IOInterface::VERBOSE);
            $this->securityScanner->scanAndOverlayResult($result);
        }

        // Dispatch event
        $this->dispatchPostUpdateCheckEvent($result);

        // Report update check result
        foreach ($reporters as $reporter) {
            $reporter->report($result);
        }

        return $result;
    }

    /**
     * @param list<Package> $packages
     * @param list<Package> $excludedPackages
     *
     * @throws Exception\ComposerInstallFailed
     * @throws Exception\ComposerUpdateFailed
     */
    private function runUpdateCheck(array $packages, array $excludedPackages): UpdateCheckResult
    {
        // Early return if no packages are listed for update check
        if ([] === $packages) {
            return new UpdateCheckResult([]);
        }

        // Ensure dependencies are installed
        $this->installDependencies();

        // Show progress
        $this->io->writeError('⏳ Checking for outdated packages...', true, IOInterface::VERBOSE);

        // Run Composer installer
        $io = new BufferIO();
        $exitCode = $this->installer->runUpdate($packages, $io);

        // Handle installer failures
        if ($exitCode > 0) {
            $this->io->writeError($io->getOutput());

            throw new Exception\ComposerUpdateFailed($exitCode);
        }

        $outdatedPackages = $this->commandResultParser->parse($io->getOutput(), $packages);

        return new UpdateCheckResult($outdatedPackages, $excludedPackages);
    }

    /**
     * @throws Exception\ComposerInstallFailed
     */
    private function installDependencies(): void
    {
        // Run Composer installer
        $io = new BufferIO();
        $exitCode = $this->installer->runInstall($io);

        // Handle installer failures
        if ($exitCode > 0) {
            $this->io->writeError($io->getOutput());

            throw new Exception\ComposerInstallFailed($exitCode);
        }
    }

    /**
     * @return array{list<Package>, list<Package>}
     */
    private function resolvePackagesForUpdateCheck(ComposerUpdateCheckConfig $config): array
    {
        $this->io->writeError('📦 Resolving packages...', true, IOInterface::VERBOSE);

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
            $excludedPackages = $requiredDevPackages;

            $this->io->writeError('🚫 Skipped dev-requirements', true, IOInterface::VERBOSE);
        }

        // Remove packages by exclude patterns
        $excludedPackages = array_merge(
            $excludedPackages,
            $this->removeByExcludePatterns($requiredPackages, $config->getExcludePatterns()),
        );

        return [
            $this->mapPackageNamesToPackage($requiredPackages),
            $this->mapPackageNamesToPackage($excludedPackages),
        ];
    }

    /**
     * @param array<non-empty-string>     $packages
     * @param list<PackageExcludePattern> $excludePatterns
     *
     * @return array<non-empty-string>
     */
    private function removeByExcludePatterns(array &$packages, array $excludePatterns): array
    {
        $excludedPackages = [];

        $packages = array_filter($packages, function (string $package) use (&$excludedPackages, $excludePatterns) {
            foreach ($excludePatterns as $excludePattern) {
                if ($excludePattern->matches($package)) {
                    $excludedPackages[] = $package;

                    $this->io->writeError(sprintf('🚫 Skipped "%s"', $package), true, IOInterface::VERBOSE);

                    return false;
                }
            }

            return true;
        });

        return $excludedPackages;
    }

    /**
     * @param array<non-empty-string> $packageNames
     *
     * @return array<Package>
     */
    private function mapPackageNamesToPackage(array $packageNames): array
    {
        return array_map(
            static fn (string $packageName) => new InstalledPackage($packageName),
            $packageNames,
        );
    }

    private function dispatchPostUpdateCheckEvent(UpdateCheckResult $result): void
    {
        $event = new PostUpdateCheckEvent($result);

        $this->composer->getEventDispatcher()->dispatch($event->getName(), $event);
    }
}
