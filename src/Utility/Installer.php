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

namespace EliasHaeussler\ComposerUpdateCheck\Utility;

use Composer\Composer;
use Composer\DependencyResolver\Request;
use Composer\Installer as ComposerInstaller;
use Composer\IO\BufferIO;

/**
 * Installer.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class Installer
{
    private static ?BufferIO $io = null;

    public static function runInstall(Composer $composer): int
    {
        self::$io = new BufferIO();
        $preferredInstall = $composer->getConfig()->get('preferred-install');
        $eventDispatcher = $composer->getEventDispatcher();

        $installer = ComposerInstaller::create(self::$io, $composer)
            ->setPreferSource('source' === $preferredInstall)
            ->setPreferDist('dist' === $preferredInstall)
            ->setDevMode(true);

        if (method_exists($eventDispatcher, 'setRunScripts')) {
            // Composer >= 2.1.2
            $eventDispatcher->setRunScripts(false);
        } else {
            // Composer < 2.1.2
            $installer->setRunScripts(false);
        }

        return $installer->run();
    }

    /**
     * @param string[] $packages
     */
    public static function runUpdate(array $packages, Composer $composer): int
    {
        self::$io = new BufferIO();
        $preferredInstall = $composer->getConfig()->get('preferred-install');

        $installer = ComposerInstaller::create(self::$io, $composer)
            ->setDryRun(true)
            ->setPreferSource('source' === $preferredInstall)
            ->setPreferDist('dist' === $preferredInstall)
            ->setDevMode(true)
            ->setUpdate(true)
            ->setUpdateAllowList($packages)
            ->setUpdateAllowTransitiveDependencies(Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS)
        ;

        return $installer->run();
    }

    public static function getLastOutput(): ?string
    {
        if (self::$io instanceof BufferIO) {
            return self::$io->getOutput();
        }

        return null;
    }
}
