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

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Composer\Composer;
use Composer\IO\IOInterface;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;
use EliasHaeussler\ComposerUpdateCheck\IO\OutputBehavior;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Utility\Installer;
use EliasHaeussler\ComposerUpdateCheck\Utility\Security;
use RuntimeException;
use Spatie\Emoji\Emoji;

/**
 * UpdateChecker.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UpdateChecker
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var OutputBehavior
     */
    private $behavior;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var string[]
     */
    private $packageBlacklist = [];

    public function __construct(Composer $composer, OutputBehavior $behavior, Options $options)
    {
        $this->composer = $composer;
        $this->behavior = $behavior;
        $this->options = $options;
    }

    public function run(): UpdateCheckResult
    {
        // Resolve packages to be checked
        $this->behavior->io->write(Emoji::package().' Resolving packages...', true, IOInterface::VERBOSE);
        $packages = $this->resolvePackagesForUpdateCheck();

        // Run update check
        $result = $this->runUpdateCheck($packages);

        // Overlay security scan
        if ($this->options->isPerformingSecurityScan() && [] !== $result->getOutdatedPackages()) {
            $this->behavior->io->write(Emoji::policeCarLight().' Checking for insecure packages...', true, IOInterface::VERBOSE);
            $result = Security::scanAndOverlayResult($result);
        }

        // Dispatch event
        $this->dispatchPostUpdateCheckEvent($result);

        return $result;
    }

    /**
     * @param string[] $packages
     */
    private function runUpdateCheck(array $packages): UpdateCheckResult
    {
        // Early return if no packages are listed for update check
        if ([] === $packages) {
            return new UpdateCheckResult([]);
        }

        // Ensure dependencies are installed
        $this->installDependencies();

        // Run Composer installer
        $this->behavior->io->write(Emoji::hourglassNotDone().' Checking for outdated packages...', true, IOInterface::VERBOSE);
        $result = Installer::runUpdate($packages, $this->composer);

        // Handle installer failures
        if ($result > 0) {
            $this->behavior->io->writeError(Installer::getLastOutput());
            throw new RuntimeException(sprintf('Error during update check. Exit code from Composer installer: %d', $result), 1600278536);
        }

        return UpdateCheckResult::fromCommandOutput(Installer::getLastOutput(), $packages);
    }

    private function installDependencies(): void
    {
        // Run Composer installer
        $result = Installer::runInstall($this->composer);

        // Handle installer failures
        if ($result > 0) {
            $this->behavior->io->writeError(Installer::getLastOutput());
            throw new RuntimeException(sprintf('Error during dependency install. Exit code from Composer installer: %d', $result), 1600614218);
        }
    }

    /**
     * @return string[]
     */
    private function resolvePackagesForUpdateCheck(): array
    {
        $rootPackage = $this->composer->getPackage();
        $requiredPackages = array_keys($rootPackage->getRequires());
        $requiredDevPackages = array_keys($rootPackage->getDevRequires());

        // Handle dev-packages
        if ($this->options->isIncludingDevPackages()) {
            $requiredPackages = array_merge($requiredPackages, $requiredDevPackages);
        } else {
            $this->packageBlacklist = array_merge($this->packageBlacklist, $requiredDevPackages);
            $this->behavior->io->write(Emoji::prohibited().' Skipped dev-requirements', true, IOInterface::VERBOSE);
        }

        // Remove blacklisted packages
        foreach ($this->options->getIgnorePackages() as $ignoredPackage) {
            $requiredPackages = $this->removeByIgnorePattern($ignoredPackage, $requiredPackages);
        }

        return $requiredPackages;
    }

    /**
     * @param string[] $packages
     *
     * @return string[]
     */
    private function removeByIgnorePattern(string $pattern, array $packages): array
    {
        return array_filter($packages, function (string $package) use ($pattern) {
            if (!fnmatch($pattern, $package)) {
                return true;
            }
            $this->behavior->io->write(sprintf('%s Skipped "%s"', Emoji::prohibited(), $package), true, IOInterface::VERBOSE);
            $this->packageBlacklist[] = $package;

            return false;
        });
    }

    private function dispatchPostUpdateCheckEvent(UpdateCheckResult $result): void
    {
        $commandEvent = new PostUpdateCheckEvent($result, $this->behavior, $this->options);
        $this->composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
    }

    /**
     * @return string[]
     */
    public function getPackageBlacklist(): array
    {
        return $this->packageBlacklist;
    }
}
