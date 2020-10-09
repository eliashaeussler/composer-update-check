<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Utility;

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
use Composer\Installer as ComposerInstaller;
use Composer\IO\BufferIO;

/**
 * Installer
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 * @internal
 */
final class Installer
{
    /**
     * @var BufferIO|null
     */
    private static $io;

    public static function runInstall(Composer $composer): int
    {
        static::$io = new BufferIO();
        $preferredInstall = $composer->getConfig()->get('preferred-install');
        $result = ComposerInstaller::create(static::$io, $composer)
            ->setPreferSource($preferredInstall === 'source')
            ->setPreferDist($preferredInstall === 'dist')
            ->setDevMode(true)
            ->setRunScripts(false)
            ->setIgnorePlatformRequirements(true)
            ->run();
        return $result;
    }

    public static function runUpdate(array $packages, Composer $composer): int
    {
        static::$io = new BufferIO();
        $preferredInstall = $composer->getConfig()->get('preferred-install');
        $installer = ComposerInstaller::create(static::$io, $composer)
            ->setDryRun(true)
            ->setPreferSource($preferredInstall === 'source')
            ->setPreferDist($preferredInstall === 'dist')
            ->setDevMode(true)
            ->setUpdate(true)
            ->setIgnorePlatformRequirements(true);
        if (method_exists($installer, 'setUpdateAllowList')) {
            $installer->setUpdateAllowList($packages);
        } else {
            /** @noinspection PhpDeprecationInspection */
            $installer->setUpdateWhitelist($packages);
        }
        return $installer->run();
    }

    public static function getLastOutput(): ?string
    {
        if (static::$io instanceof BufferIO) {
            return static::$io->getOutput();
        }
        return null;
    }
}
