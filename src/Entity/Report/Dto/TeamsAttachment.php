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

/**
 * TeamsAttachment.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TeamsAttachment implements JsonSerializable
{
    /**
     * @param array<string, mixed> $content
     */
    private function __construct(
        public readonly string $contentType,
        public readonly array $content,
    ) {}

    /**
     * @param list<TeamsContent> $body
     * @param list<TeamsAction>  $actions
     *
     * @see https://github.com/microsoft/AdaptiveCards/blob/main/schemas/src/AdaptiveCard.json
     */
    public static function adaptiveCard(array $body, string $fallbackText, array $actions = []): self
    {
        return new self(
            'application/vnd.microsoft.card.adaptive',
            [
                'type' => 'AdaptiveCard',
                'version' => '1.5',
                'body' => $body,
                'msteams' => [
                    'width' => 'Full',
                ],
                'fallbackText' => $fallbackText,
                'actions' => $actions,
            ],
        );
    }

    /**
     * @return array{
     *     contentType: string,
     *     content: array<string, mixed>,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'contentType' => $this->contentType,
            'content' => $this->content,
        ];
    }
}
