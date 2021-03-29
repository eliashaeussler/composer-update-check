<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateCheck\Utility;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2021 Elias Häußler <elias@haeussler.dev>
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
use Composer\Package\PackageInterface;
use Composer\Semver\Constraint\Constraint;

/**
 * PackageManager.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class PackageManager
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function isPackageInstalled(string $packagedName, Constraint $constraint = null): bool
    {
        $package = $this->getPackage($packagedName);

        if (null === $package) {
            return false;
        }
        if (null === $constraint) {
            return true;
        }

        return $constraint->matchSpecific($this->getConstraint($package->getVersion()));
    }

    public function suggestRequirement(string $packageName, string $suggestion): void
    {
        if (!$this->isPackageInstalled($packageName)) {
            return;
        }
        $this->io->writeError(sprintf(
            '<warning>Package %s (installed as %s) might be an incompatible requirement. Suggested requirement: %s</warning>',
            $packageName,
            $this->getPackage($packageName)->getPrettyVersion(),
            $suggestion
        ));
    }

    public function getPackage(string $packageName): ?PackageInterface
    {
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->findPackages($packageName);

        // Early return if package is not installed
        if (empty($packages)) {
            return null;
        }

        return reset($packages);
    }

    private function getConstraint(string $version): Constraint
    {
        return new Constraint('==', $version);
    }
}
