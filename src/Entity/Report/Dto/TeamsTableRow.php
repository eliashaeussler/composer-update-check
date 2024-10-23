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

namespace EliasHaeussler\ComposerUpdateCheck\Entity\Report\Dto;

use JsonSerializable;

use function array_filter;

/**
 * TeamsTableRow.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://github.com/microsoft/AdaptiveCards/blob/main/schemas/src/elements/TableRow.json
 */
final class TeamsTableRow implements JsonSerializable
{
    /**
     * @param list<TeamsTableCell> $cells
     */
    public function __construct(
        public readonly array $cells,
        public readonly ?string $spacing = null,
        public readonly ?string $horizontalCellContentAlignment = null,
        public readonly ?string $verticalCellContentAlignment = null,
    ) {}

    /**
     * @return array{
     *     type: string,
     *     cells: list<TeamsTableCell>,
     *     spacing?: string,
     *     horizontalCellContentAlignment?: string,
     *     verticalCellContentAlignment?: string,
     * }
     */
    public function jsonSerialize(): array
    {
        $row = [
            'type' => 'TableRow',
            'cells' => $this->cells,
            'spacing' => $this->spacing,
            'horizontalCellContentAlignment' => $this->horizontalCellContentAlignment,
            'verticalCellContentAlignment' => $this->verticalCellContentAlignment,
        ];

        return array_filter($row, static fn (mixed $value) => null !== $value);
    }
}
