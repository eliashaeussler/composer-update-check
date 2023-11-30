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

namespace EliasHaeussler\ComposerUpdateCheck\Security;

use Composer\Semver\Semver;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\InsecurePackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\OutdatedPackage;
use InvalidArgumentException;

/**
 * ScanResult.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class ScanResult
{
    /**
     * @param InsecurePackage[] $insecurePackages
     */
    public function __construct(
        private array $insecurePackages,
    ) {
        $this->validateInsecurePackages();
    }

    /**
     * @param array<string, mixed> $apiResult
     */
    public static function fromApiResult(array $apiResult): self
    {
        // Early return if no advisories were provided
        if (
            !array_key_exists('advisories', $apiResult)
            || !is_array($apiResult['advisories'])
            || [] === $apiResult['advisories']
        ) {
            return new self([]);
        }

        // Parse security advisories
        $insecurePackages = [];
        $advisories = $apiResult['advisories'];

        foreach ($advisories as $packageName => $packageAdvisories) {
            $affectedVersions = [];

            foreach ($packageAdvisories as $packageAdvisory) {
                $affectedVersions[] = $packageAdvisory['affectedVersions'];
            }

            if ([] !== $affectedVersions) {
                $insecurePackages[] = new InsecurePackage($packageName, $affectedVersions);
            }
        }

        return new self($insecurePackages);
    }

    /**
     * @return InsecurePackage[]
     */
    public function getInsecurePackages(): array
    {
        return $this->insecurePackages;
    }

    public function isInsecure(OutdatedPackage $outdatedPackage): bool
    {
        foreach ($this->insecurePackages as $insecurePackage) {
            if ($insecurePackage->getName() === $outdatedPackage->getName()) {
                $insecureVersions = implode('|', $insecurePackage->getAffectedVersions());

                return Semver::satisfies($outdatedPackage->getOutdatedVersion()->get(), $insecureVersions);
            }
        }

        return false;
    }

    private function validateInsecurePackages(): void
    {
        foreach ($this->insecurePackages as $key => $insecurePackage) {
            if (!($insecurePackage instanceof InsecurePackage)) {
                throw new InvalidArgumentException(sprintf('Insecure package #%s must be an instance of "%s".', $key, InsecurePackage::class), 1610707087);
            }
        }
    }
}
