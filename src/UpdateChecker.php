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
use EliasHaeussler\ComposerUpdateCheck\Composer\ComposerInstaller;
use EliasHaeussler\ComposerUpdateCheck\Configuration\ComposerUpdateCheckConfig;
use EliasHaeussler\ComposerUpdateCheck\Configuration\Options\PackageExcludePattern;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\InstalledPackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\Package;
use EliasHaeussler\ComposerUpdateCheck\Entity\Result\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;
use EliasHaeussler\ComposerUpdateCheck\Exception\ComposerInstallFailed;
use EliasHaeussler\ComposerUpdateCheck\Exception\ComposerUpdateFailed;
use EliasHaeussler\ComposerUpdateCheck\Security\SecurityScanner;

use function array_map;
use function array_merge;

/**
 * UpdateChecker.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class UpdateChecker
{
    public function __construct(
        private Composer $composer,
        private ComposerInstaller $installer,
        private IOInterface $io,
        private SecurityScanner $securityScanner,
    ) {}

    public function run(ComposerUpdateCheckConfig $config): UpdateCheckResult
    {
        [$packages, $excludedPackages] = $this->resolvePackagesForUpdateCheck($config);
        $result = $this->runUpdateCheck($packages, $excludedPackages);

        // Overlay security scan
        if ($config->shouldPerformSecurityScan() && [] !== $result->getOutdatedPackages()) {
            $this->io->writeError('🚨 Checking for insecure packages...', true, IOInterface::VERBOSE);
            $this->securityScanner->scanAndOverlayResult($result);
        }

        // Dispatch event
        $this->dispatchPostUpdateCheckEvent($result);

        return $result;
    }

    /**
     * @param list<Package> $packages
     * @param list<Package> $excludedPackages
     *
     * @throws ComposerInstallFailed
     * @throws ComposerUpdateFailed
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

            throw new ComposerUpdateFailed($exitCode);
        }

        return UpdateCheckResult::fromCommandOutput($io->getOutput(), $packages, $excludedPackages);
    }

    /**
     * @throws ComposerInstallFailed
     */
    private function installDependencies(): void
    {
        // Run Composer installer
        $io = new BufferIO();
        $exitCode = $this->installer->runInstall($io);

        // Handle installer failures
        if ($exitCode > 0) {
            $this->io->writeError($io->getOutput());

            throw new ComposerInstallFailed($exitCode);
        }
    }

    /**
     * @return array{list<Package>, list<Package>}
     */
    private function resolvePackagesForUpdateCheck(ComposerUpdateCheckConfig $config): array
    {
        $this->io->writeError('📦 Resolving packages...', true, IOInterface::VERBOSE);

        $rootPackage = $this->composer->getPackage();
        $requiredPackages = array_keys($rootPackage->getRequires());
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
     * @param string[]                    $packages
     * @param list<PackageExcludePattern> $excludePatterns
     *
     * @return string[]
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
     * @param array<string> $packageNames
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
