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
 * TeamsAction.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class TeamsAction implements JsonSerializable
{
    /**
     * @param list<string>|null $targetElements
     */
    private function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly ?array $targetElements = null,
        public readonly ?string $title = null,
    ) {}

    /**
     * @param list<string> $targetElements
     */
    public static function toggleVisibility(string $id, string $title, array $targetElements): self
    {
        return new self(
            type: 'Action.ToggleVisibility',
            id: $id,
            targetElements: $targetElements,
            title: $title,
        );
    }

    /**
     * @return array{
     *     type: string,
     *     id: string,
     *     targetElements?: list<string>,
     *     title?: string,
     * }
     */
    public function jsonSerialize(): array
    {
        $action = [
            'type' => $this->type,
            'id' => $this->id,
            'targetElements' => $this->targetElements,
            'title' => $this->title,
        ];

        return array_filter($action, static fn (mixed $value) => null !== $value);
    }
}
