<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2024 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\ComposerUpdateCheck\Entity\Security;

use function usort;

/**
 * SeverityLevel.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://nvd.nist.gov/vuln-metrics/cvss
 */
enum SeverityLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function compareTo(self $other): int
    {
        return $this->getInternalLevel() <=> $other->getInternalLevel();
    }

    public static function getHighestSeverityLevel(self ...$severityLevels): self
    {
        usort($severityLevels, static fn (self $a, self $b) => $a->compareTo($b));

        $highestSeverityLevel = array_pop($severityLevels);

        if (null === $highestSeverityLevel) {
            return self::Low;
        }

        return $highestSeverityLevel;
    }

    private function getInternalLevel(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }
}
