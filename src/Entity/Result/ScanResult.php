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

use Composer\Semver;
use EliasHaeussler\ComposerUpdateCheck\Entity;

/**
 * ScanResult.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ScanResult
{
    /**
     * @param array<string, list<Entity\Security\SecurityAdvisory>> $securityAdvisories
     */
    public function __construct(
        private readonly array $securityAdvisories,
    ) {}

    /**
     * @return array<string, list<Entity\Security\SecurityAdvisory>>
     */
    public function getSecurityAdvisories(): array
    {
        return $this->securityAdvisories;
    }

    /**
     * @return list<Entity\Security\SecurityAdvisory>
     */
    public function getSecurityAdvisoriesForPackage(Entity\Package\OutdatedPackage $package): array
    {
        $packageVersion = $package->getOutdatedVersion()->toString();
        $securityAdvisories = [];

        foreach ($this->securityAdvisories[$package->getName()] ?? [] as $securityAdvisory) {
            if (Semver\Semver::satisfies($packageVersion, $securityAdvisory->getAffectedVersions())) {
                $securityAdvisories[] = $securityAdvisory;
            }
        }

        return $securityAdvisories;
    }

    public function isPackageInsecure(Entity\Package\OutdatedPackage $package): bool
    {
        return [] !== $this->getSecurityAdvisoriesForPackage($package);
    }
}
