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

/**
 * UpdateCheckResult.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateCheckResult
{
    private const COMMAND_OUTPUT_PATTERN =
        '#^'.
            '\\s*- Upgrading '.
            '(?P<name>\\S+) \\('.
                '(?P<outdated>dev-\\S+ \\S+|(?!dev-)\\S+)'.
                ' => '.
                '(?P<new>dev-\S+ \S+|(?!dev-)\S+)'.
            '\\)'.
        '$#';

    /**
     * @var list<Package\OutdatedPackage>
     */
    private readonly array $outdatedPackages;

    /**
     * @var list<Package\Package>
     */
    private readonly array $excludedPackages;

    /**
     * @param list<Package\OutdatedPackage> $outdatedPackages
     * @param list<Package\Package>         $excludedPackages
     */
    public function __construct(array $outdatedPackages, array $excludedPackages = [])
    {
        $this->outdatedPackages = $this->sortPackages($outdatedPackages);
        $this->excludedPackages = $this->sortPackages($excludedPackages);
    }

    /**
     * @param list<Package\Package> $allowedPackages
     * @param list<Package\Package> $excludedPackages
     */
    public static function fromCommandOutput(
        string $output,
        array $allowedPackages,
        array $excludedPackages = [],
    ): self {
        $allowedPackageNames = array_map(
            static fn (Package\Package $package) => $package->getName(),
            $allowedPackages,
        );

        $outputParts = explode(PHP_EOL, $output);
        $outdatedPackages = array_unique(
            array_filter(
                array_map(self::parseCommandOutput(...), $outputParts),
                static function (?Package\OutdatedPackage $outdatedPackage) use ($allowedPackageNames) {
                    if (null === $outdatedPackage) {
                        return false;
                    }

                    return in_array($outdatedPackage->getName(), $allowedPackageNames, true);
                },
            ),
            SORT_REGULAR,
        );

        return new self($outdatedPackages, $excludedPackages);
    }

    /**
     * @return list<Package\OutdatedPackage>
     */
    public function getOutdatedPackages(): array
    {
        return $this->outdatedPackages;
    }

    /**
     * @return list<Package\Package>
     */
    public function getExcludedPackages(): array
    {
        return $this->excludedPackages;
    }

    private static function parseCommandOutput(string $output): ?Package\OutdatedPackage
    {
        if (1 !== preg_match(self::COMMAND_OUTPUT_PATTERN, $output, $matches)) {
            return null;
        }

        return new Package\OutdatedPackage(
            $matches['name'],
            new Package\Version($matches['outdated']),
            new Package\Version($matches['new']),
        );
    }

    /**
     * @template T of Package\Package
     *
     * @param list<T> $packages
     *
     * @return list<T>
     */
    private function sortPackages(array $packages): array
    {
        usort($packages, static fn (Package\Package $a, Package\Package $b) => strcmp($a->getName(), $b->getName()));

        return $packages;
    }
}
