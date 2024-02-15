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
 * ComposerUpdateResult.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ComposerUpdateResult
{
    /**
     * @param non-negative-int                     $exitCode
     * @param list<Entity\Package\OutdatedPackage> $outdatedPackages
     */
    public function __construct(
        private readonly int $exitCode,
        private readonly array $outdatedPackages,
    ) {}

    /**
     * @return non-negative-int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->exitCode;
    }

    /**
     * @return list<Entity\Package\OutdatedPackage>
     */
    public function getOutdatedPackages(): array
    {
        return $this->outdatedPackages;
    }
}
