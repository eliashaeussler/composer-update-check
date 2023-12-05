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

namespace EliasHaeussler\ComposerUpdateCheck\Composer;

use EliasHaeussler\ComposerUpdateCheck\Entity\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\Package;
use EliasHaeussler\ComposerUpdateCheck\Entity\Version;

use function array_map;
use function array_values;
use function in_array;
use function preg_match_all;

/**
 * CommandResultParser.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class CommandResultParser
{
    private const COMMAND_OUTPUT_PATTERN =
        '#^'.
            '\s*- Upgrading '.
            '(?P<name>\S+) \('.
                '(?P<outdated>dev-\S+ \S+|(?!dev-)\S+)'.
                ' => '.
                '(?P<new>dev-\S+ \S+|(?!dev-)\S+)'.
            '\)'.
        '$#m';

    /**
     * @param list<Package> $packages
     *
     * @return list<OutdatedPackage>
     */
    public function parse(string $output, array $packages): array
    {
        $outdatedPackages = [];
        $allowedPackageNames = array_map(
            static fn (Package $package) => $package->getName(),
            $packages,
        );

        // Early return on regex failure
        if (false === preg_match_all(self::COMMAND_OUTPUT_PATTERN, $output, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as ['name' => $packageName, 'outdated' => $outdatedVersion, 'new' => $newVersion]) {
            if (!in_array($packageName, $allowedPackageNames, true)) {
                continue;
            }

            if (!isset($outdatedPackages[$packageName])) {
                $outdatedPackages[$packageName] = new OutdatedPackage(
                    $packageName,
                    new Version($outdatedVersion),
                    new Version($newVersion),
                );
            }
        }

        return array_values($outdatedPackages);
    }
}
