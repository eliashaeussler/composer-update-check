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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\ComposerUpdateCheck\Utility;

use Composer\Plugin\PluginInterface;
use InvalidArgumentException;

/**
 * Composer.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class Composer
{
    public const VERSION_FULL = 0;
    public const VERSION_MAJOR = 1;
    public const VERSION_BRANCH = 2;

    public static function getPlatformVersion(int $versionType = self::VERSION_FULL): string
    {
        $platformVersion = PluginInterface::PLUGIN_API_VERSION;
        $versionComponents = explode('.', $platformVersion);

        switch ($versionType) {
            case self::VERSION_FULL:
                return $platformVersion;
            case self::VERSION_MAJOR:
                return $versionComponents[0];
            case self::VERSION_BRANCH:
                return $versionComponents[0].'.'.$versionComponents[1];
            default:
                throw new InvalidArgumentException('The given version type is not supported.', 1603794822);
        }
    }

    public static function getMajorVersion(): int
    {
        return (int) self::getPlatformVersion(self::VERSION_MAJOR);
    }
}
