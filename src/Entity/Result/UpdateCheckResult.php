<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020-2024 Elias Häußler <elias@haeussler.dev>
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

use EliasHaeussler\ComposerUpdateCheck\Entity;

/**
 * UpdateCheckResult.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateCheckResult
{
    /**
     * @var list<Entity\Package\OutdatedPackage>
     */
    private readonly array $outdatedPackages;

    /**
     * @var list<Entity\Package\Package>
     */
    private readonly array $excludedPackages;

    /**
     * @param list<Entity\Package\OutdatedPackage> $outdatedPackages
     * @param list<Entity\Package\Package>         $excludedPackages
     */
    public function __construct(array $outdatedPackages, array $excludedPackages = [])
    {
        $this->outdatedPackages = $this->sortPackages($outdatedPackages);
        $this->excludedPackages = $this->sortPackages($excludedPackages);
    }

    /**
     * @return list<Entity\Package\OutdatedPackage>
     */
    public function getOutdatedPackages(): array
    {
        return $this->outdatedPackages;
    }

    /**
     * @return list<Entity\Package\Package>
     */
    public function getExcludedPackages(): array
    {
        return $this->excludedPackages;
    }

    /**
     * @return list<Entity\Security\SecurityAdvisory>
     */
    public function getSecurityAdvisories(): array
    {
        $securityAdvisories = [];

        foreach ($this->outdatedPackages as $outdatedPackage) {
            foreach ($outdatedPackage->getSecurityAdvisories() as $securityAdvisory) {
                $securityAdvisories[] = $securityAdvisory;
            }
        }

        return $securityAdvisories;
    }

    /**
     * @return list<Entity\Package\OutdatedPackage>
     */
    public function getInsecureOutdatedPackages(): array
    {
        $insecurePackages = [];

        foreach ($this->outdatedPackages as $outdatedPackage) {
            if ($outdatedPackage->isInsecure()) {
                $insecurePackages[] = $outdatedPackage;
            }
        }

        return $insecurePackages;
    }

    public function hasInsecureOutdatedPackages(): bool
    {
        return [] !== $this->getInsecureOutdatedPackages();
    }

    /**
     * @template T of Entity\Package\Package
     *
     * @param list<T> $packages
     *
     * @return list<T>
     */
    private function sortPackages(array $packages): array
    {
        usort(
            $packages,
            static fn (Entity\Package\Package $a, Entity\Package\Package $b) => strcmp($a->getName(), $b->getName()),
        );

        return $packages;
    }
}
