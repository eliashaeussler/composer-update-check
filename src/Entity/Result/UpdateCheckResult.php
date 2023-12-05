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

namespace EliasHaeussler\ComposerUpdateCheck\Entity\Result;

use EliasHaeussler\ComposerUpdateCheck\Entity\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\Package;

/**
 * UpdateCheckResult.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateCheckResult
{
    /**
     * @var list<OutdatedPackage>
     */
    private readonly array $outdatedPackages;

    /**
     * @var list<Package>
     */
    private readonly array $excludedPackages;

    /**
     * @param list<OutdatedPackage> $outdatedPackages
     * @param list<Package>         $excludedPackages
     */
    public function __construct(array $outdatedPackages, array $excludedPackages = [])
    {
        $this->outdatedPackages = $this->sortPackages($outdatedPackages);
        $this->excludedPackages = $this->sortPackages($excludedPackages);
    }

    /**
     * @return list<OutdatedPackage>
     */
    public function getOutdatedPackages(): array
    {
        return $this->outdatedPackages;
    }

    /**
     * @return list<Package>
     */
    public function getExcludedPackages(): array
    {
        return $this->excludedPackages;
    }

    /**
     * @template T of Package
     *
     * @param list<T> $packages
     *
     * @return list<T>
     */
    private function sortPackages(array $packages): array
    {
        usort(
            $packages,
            static fn (Package $a, Package $b) => strcmp($a->getName(), $b->getName()),
        );

        return $packages;
    }
}
