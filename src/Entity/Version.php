<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020-2026 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\ComposerUpdateCheck\Entity;

use Stringable;

use function sprintf;
use function substr;

/**
 * Version.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class Version implements Stringable
{
    public function __construct(
        private readonly string $version,
        private readonly ?string $sha1 = null,
    ) {}

    public function prettyVersion(): string
    {
        return $this->version;
    }

    public function sha1(bool $short = false): ?string
    {
        if (null === $this->sha1) {
            return null;
        }

        return $short ? substr($this->sha1, 0, 7) : $this->sha1;
    }

    public function toString(): string
    {
        $versionString = $this->version;
        $sha1 = $this->sha1(true);

        if ('' !== (string) $sha1) {
            $versionString .= sprintf(' (%s)', $sha1);
        }

        return $versionString;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
