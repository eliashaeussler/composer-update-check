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

namespace EliasHaeussler\ComposerUpdateCheck\Package;

use InvalidArgumentException;

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

/**
 * UpdateCheckResult.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UpdateCheckResult
{
    protected const COMMAND_OUTPUT_PATTERN =
        '#^'.
            '\\s*- Upgrading '.
            '(?P<name>\\S+) \\('.
                '(?P<outdated>dev-\\S+ \\S+|(?!dev-)\\S+)'.
                ' => '.
                '(?P<new>dev-\S+ \S+|(?!dev-)\S+)'.
            '\\)'.
        '$#';
    protected const LEGACY_COMMAND_OUTPUT_PATTERN =
        '#^'.
            '\\s*- Updating '.
            '(?P<name>dev-\\S+ \\S+|(?!dev-)\\S+) \\('.
                '(?P<outdated>dev-\S+ \S+|(?!dev-)\S+)'.
            '\\) to \\S+ \\('.
                '(?P<new>dev-\S+ \S+|(?!dev-)\S+)'.
            '\\)'.
        '$#';

    /**
     * @var OutdatedPackage[]
     */
    private $outdatedPackages;

    /**
     * @param OutdatedPackage[] $outdatedPackages
     */
    public function __construct(array $outdatedPackages)
    {
        $this->outdatedPackages = $outdatedPackages;
        $this->validateOutdatedPackages();
        $this->sortPackages($this->outdatedPackages);
    }

    /**
     * @return OutdatedPackage[]
     */
    public function getOutdatedPackages(): array
    {
        return $this->outdatedPackages;
    }

    /**
     * @param string[] $allowedPackages
     */
    public static function fromCommandOutput(string $output, array $allowedPackages): self
    {
        $outputParts = explode(PHP_EOL, $output);
        $packages = array_unique(
            array_filter(
                array_map([static::class, 'parseCommandOutput'], $outputParts),
                function (?OutdatedPackage $outdatedPackage) use ($allowedPackages) {
                    if (null === $outdatedPackage) {
                        return false;
                    }

                    return in_array($outdatedPackage->getName(), $allowedPackages, true);
                },
            ),
            SORT_REGULAR,
        );

        return new self($packages);
    }

    public static function parseCommandOutput(string $output): ?OutdatedPackage
    {
        if (
            !preg_match(static::COMMAND_OUTPUT_PATTERN, $output, $matches)
            && !preg_match(static::LEGACY_COMMAND_OUTPUT_PATTERN, $output, $matches)
        ) {
            return null;
        }
        $packageName = $matches['name'];
        $outdatedVersion = $matches['outdated'];
        $newVersion = $matches['new'];

        return new OutdatedPackage($packageName, $outdatedVersion, $newVersion);
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     */
    private function sortPackages(array &$outdatedPackages): void
    {
        usort($outdatedPackages, function (OutdatedPackage $a, OutdatedPackage $b) {
            return strcmp($a->getName(), $b->getName());
        });
    }

    private function validateOutdatedPackages(): void
    {
        foreach ($this->outdatedPackages as $key => $outdatedPackage) {
            if (!($outdatedPackage instanceof OutdatedPackage)) {
                throw new InvalidArgumentException(sprintf('Outdated package #%s must be an instance of "%s".', $key, OutdatedPackage::class), 1600276584);
            }
        }
    }
}
