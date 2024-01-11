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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Fixtures\TestImplementations;

use EliasHaeussler\ComposerUpdateCheck\Entity;
use EliasHaeussler\ComposerUpdateCheck\Reporter;

/**
 * Dummy2Reporter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class Dummy2Reporter implements Reporter\Reporter
{
    public const NAME = 'dummy-2';

    /**
     * @var list<array{Entity\Result\UpdateCheckResult, array<string, mixed>}>
     */
    public array $reportedResults = [];

    public function report(Entity\Result\UpdateCheckResult $result, array $options): bool
    {
        $this->reportedResults[] = [$result, $options];

        return true;
    }

    public static function getName(): string
    {
        return self::NAME;
    }
}
