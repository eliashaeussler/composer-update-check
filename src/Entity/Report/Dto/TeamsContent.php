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
 * TeamsContent.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class TeamsContent implements JsonSerializable
{
    /**
     * @param list<TeamsTableColumn>|null $columns
     * @param list<TeamsFact>|null        $facts
     * @param list<self>|null             $items
     * @param list<TeamsTableRow>|null    $rows
     */
    private function __construct(
        public readonly string $type,
        public readonly ?array $columns = null,
        public readonly ?array $facts = null,
        public readonly ?bool $firstRowAsHeader = null,
        public readonly ?string $gridStyle = null,
        public readonly ?string $id = null,
        public readonly ?bool $isVisible = null,
        public readonly ?array $items = null,
        public readonly ?array $rows = null,
        public readonly ?string $size = null,
        public readonly ?string $spacing = null,
        public readonly ?string $text = null,
        public readonly ?string $weight = null,
        public readonly ?bool $wrap = null,
    ) {}

    /**
     * @param list<self> $items
     */
    public static function container(
        array $items,
        bool $isVisible = true,
        string $id = null,
        string $spacing = null,
    ): self {
        return new self(
            type: 'Container',
            id: $id,
            isVisible: $isVisible,
            items: $items,
            spacing: $spacing,
        );
    }

    /**
     * @param list<TeamsFact> $facts
     */
    public static function factSet(array $facts, string $spacing = null): self
    {
        return new self(
            type: 'FactSet',
            facts: $facts,
            spacing: $spacing,
        );
    }

    /**
     * @param list<TeamsTableColumn> $columns
     * @param list<TeamsTableRow>    $rows
     */
    public static function table(
        array $columns,
        array $rows,
        bool $firstRowAsHeader = false,
        string $gridStyle = null,
    ): self {
        return new self(
            type: 'Table',
            columns: $columns,
            firstRowAsHeader: $firstRowAsHeader,
            gridStyle: $gridStyle,
            rows: $rows,
        );
    }

    public static function textBlock(
        string $text,
        bool $wrap = false,
        string $size = null,
        string $spacing = null,
        string $weight = null,
    ): self {
        return new self(
            type: 'TextBlock',
            size: $size,
            spacing: $spacing,
            text: $text,
            weight: $weight,
            wrap: $wrap,
        );
    }

    /**
     * @return array{
     *     type: string,
     *     columns?: list<TeamsTableColumn>,
     *     facts?: list<TeamsFact>,
     *     firstRowAsHeader?: bool,
     *     gridStyle?: string,
     *     id?: string,
     *     isVisible?: bool,
     *     items?: list<self>,
     *     rows?: list<TeamsTableRow>,
     *     size?: string,
     *     spacing?: string,
     *     text?: string,
     *     weight?: string,
     *     wrap?: bool,
     * }
     */
    public function jsonSerialize(): array
    {
        $body = [
            'type' => $this->type,
            'columns' => $this->columns,
            'facts' => $this->facts,
            'firstRowAsHeader' => $this->firstRowAsHeader,
            'gridStyle' => $this->gridStyle,
            'id' => $this->id,
            'isVisible' => $this->isVisible,
            'items' => $this->items,
            'rows' => $this->rows,
            'size' => $this->size,
            'spacing' => $this->spacing,
            'text' => $this->text,
            'weight' => $this->weight,
            'wrap' => $this->wrap,
        ];

        return array_filter($body, static fn (mixed $value) => null !== $value);
    }
}
