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

namespace EliasHaeussler\ComposerUpdateCheck\IO;

use InvalidArgumentException;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2021 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Style.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Style
{
    public const NORMAL = 'normal';
    public const JSON = 'json';

    /**
     * @var string
     */
    private $style;

    public function __construct(string $style = self::NORMAL)
    {
        $this->style = $style;
        $this->validate();
    }

    public static function isSupported(string $style): bool
    {
        return in_array($style, [self::NORMAL, self::JSON], true);
    }

    public function isNormal(): bool
    {
        return $this->is(self::NORMAL);
    }

    public function isJson(): bool
    {
        return $this->is(self::JSON);
    }

    public function is(string $style): bool
    {
        return $this->style === $style;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    private function validate(): void
    {
        if (!static::isSupported($this->style)) {
            throw new InvalidArgumentException('The given style is not supported.', 1617549657);
        }
    }
}
