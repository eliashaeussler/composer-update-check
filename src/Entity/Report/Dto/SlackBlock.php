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
 * SlackBlock.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class SlackBlock implements JsonSerializable
{
    /**
     * @param list<SlackBlockElement>|null $fields
     * @param list<SlackBlockElement>|null $elements
     */
    private function __construct(
        public readonly string $type,
        public readonly ?SlackBlockElement $text = null,
        public readonly ?array $fields = null,
        public readonly ?array $elements = null,
    ) {}

    /**
     * @param list<SlackBlockElement> $elements
     *
     * @see https://api.slack.com/reference/block-kit/blocks#context
     */
    public static function context(array $elements): self
    {
        return new self('context', elements: $elements);
    }

    /**
     * @see https://api.slack.com/reference/block-kit/blocks#header
     */
    public static function header(SlackBlockElement $text): self
    {
        return new self('header', $text);
    }

    /**
     * @param list<SlackBlockElement>|null $fields
     *
     * @see https://api.slack.com/reference/block-kit/blocks#section
     */
    public static function section(
        ?SlackBlockElement $text = null,
        ?array $fields = null,
    ): self {
        return new self('section', $text, $fields);
    }

    /**
     * @return array{
     *     type: string,
     *     text?: SlackBlockElement,
     *     fields?: list<SlackBlockElement>,
     *     elements?: list<SlackBlockElement>,
     * }
     */
    public function jsonSerialize(): array
    {
        $json = [
            'type' => $this->type,
        ];

        if (null !== $this->text) {
            $json['text'] = $this->text;
        }

        if (null !== $this->fields) {
            $json['fields'] = $this->fields;
        }

        if (null !== $this->elements) {
            $json['elements'] = $this->elements;
        }

        return $json;
    }
}
