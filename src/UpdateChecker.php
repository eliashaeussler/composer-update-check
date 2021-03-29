<?php
declare(strict_types=1);
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
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Plugin\PluginEvents;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Utility\Installer;
use EliasHaeussler\ComposerUpdateCheck\Utility\Security;
use Spatie\Emoji\Emoji;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * UpdateChecker
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
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var string[]
     */
    private $packageBlacklist = [];

    /**
     * @var bool
     */
    private $securityScan = false;

    public function __construct(Composer $composer, InputInterface $input = null, OutputInterface $output = null)
    {
        $this->composer = $composer;
        $this->input = $input ?? new ArrayInput([]);
        $this->output = $output ?? new NullOutput();
        if ($input !== null && $output !== null) {
            $this->io = new ConsoleIO($input, $output, new HelperSet());
        } else {
            $this->io = new NullIO();
        }
    }

    public function run(array $packageBlacklist = [], bool $includeDevPackages = true): UpdateCheckResult
    {
        // Resolve packages to be checked
        $this->io->write(Emoji::package() . ' Resolving packages...', true, IOInterface::VERBOSE);
        $packages = $this->resolvePackagesForUpdateCheck($packageBlacklist, $includeDevPackages);

        // Run update check
        $result = $this->runUpdateCheck($packages);

        // Overlay security scan
        if ($this->securityScan && $result->getOutdatedPackages() !== []) {
            $this->io->write(Emoji::policeCarLight() . ' Checking for insecure packages...', true, IOInterface::VERBOSE);
            $result = Security::scanAndOverlayResult($result);
        }

        // Dispatch event
        $this->dispatchPostUpdateCheckEvent($result);

        return $result;
    }

    private function runUpdateCheck(array $packages): UpdateCheckResult
    {
        // Early return if no packages are listed for update check
        if ($packages === []) {
            return new UpdateCheckResult([]);
        }

        // Ensure dependencies are installed
        $this->installDependencies();

        // Run Composer installer
        $this->io->write(Emoji::hourglassNotDone() . ' Checking for outdated packages...', true, IOInterface::VERBOSE);
        $result = Installer::runUpdate($packages, $this->composer);

        // Handle installer failures
        if ($result > 0) {
            $this->io->writeError(Installer::getLastOutput());
            throw new \RuntimeException(
                sprintf('Error during update check. Exit code from Composer installer: %d', $result),
                1600278536
            );
        }

        return UpdateCheckResult::fromCommandOutput(Installer::getLastOutput(), $packages);
    }

    private function installDependencies(): void
    {
        // Run Composer installer
        $result = Installer::runInstall($this->composer);

        // Handle installer failures
        if ($result > 0) {
            $this->io->writeError(Installer::getLastOutput());
            throw new \RuntimeException(
                sprintf('Error during dependency install. Exit code from Composer installer: %d', $result),
                1600614218
            );
        }
    }

    private function resolvePackagesForUpdateCheck(array $ignoredPackages, bool $includeDevPackages): array
    {
        $rootPackage = $this->composer->getPackage();
        $requiredPackages = array_keys($rootPackage->getRequires());
        $requiredDevPackages = array_keys($rootPackage->getDevRequires());

        // Handle dev-packages
        if ($includeDevPackages) {
            $requiredPackages = array_merge($requiredPackages, $requiredDevPackages);
        } else {
            $this->packageBlacklist = array_merge($this->packageBlacklist, $requiredDevPackages);
            $this->io->write(Emoji::prohibited() . ' Skipped dev-requirements', true, IOInterface::VERBOSE);
        }

        // Remove blacklisted packages
        foreach ($ignoredPackages as $ignoredPackage) {
            $requiredPackages = $this->removeByIgnorePattern($ignoredPackage, $requiredPackages);
        }

        return $requiredPackages;
    }

    private function removeByIgnorePattern(string $pattern, array $packages): array
    {
        return array_filter($packages, function (string $package) use ($pattern) {
            if (!fnmatch($pattern, $package)) {
                return true;
            }
            $this->io->write(sprintf('%s Skipped "%s"', Emoji::prohibited(), $package), true, IOInterface::VERBOSE);
            $this->packageBlacklist[] = $package;
            return false;
        });
    }

    private function dispatchPostUpdateCheckEvent(UpdateCheckResult $result): void
    {
        $commandEvent = new PostUpdateCheckEvent(
            PluginEvents::COMMAND,
            'update-check',
            $this->input,
            $this->output,
            [],
            [],
            $result
        );
        $this->composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
    }

    public function getPackageBlacklist(): array
    {
        return $this->packageBlacklist;
    }

    public function setSecurityScan(bool $securityScan): self
    {
        $this->securityScan = $securityScan;
        return $this;
    }
}
