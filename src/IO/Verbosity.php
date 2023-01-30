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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\ComposerUpdateCheck\IO;

use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verbosity.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Verbosity
{
    public const QUIET = OutputInterface::VERBOSITY_QUIET;
    public const NORMAL = OutputInterface::VERBOSITY_NORMAL;
    public const VERBOSE = OutputInterface::VERBOSITY_VERBOSE;
    public const VERY_VERBOSE = OutputInterface::VERBOSITY_VERY_VERBOSE;
    public const DEBUG = OutputInterface::VERBOSITY_DEBUG;

    /**
     * @var int
     */
    private $level;

    public function __construct(int $level = OutputInterface::VERBOSITY_NORMAL)
    {
        $this->level = $level;
        $this->validate();
    }

    public static function isSupported(int $level): bool
    {
        return in_array($level, [
            self::QUIET,
            self::NORMAL,
            self::VERBOSE,
            self::VERY_VERBOSE,
            self::DEBUG,
        ], true);
    }

    public function isQuiet(): bool
    {
        return $this->is(self::QUIET);
    }

    public function isNormal(): bool
    {
        return $this->is(self::NORMAL);
    }

    public function isVerbose(): bool
    {
        return $this->is(self::VERBOSE);
    }

    public function isVeryVerbose(): bool
    {
        return $this->is(self::VERY_VERBOSE);
    }

    public function isDebug(): bool
    {
        return $this->is(self::DEBUG);
    }

    public function is(int $level): bool
    {
        return $this->level === $level;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    private function validate(): void
    {
        if (!static::isSupported($this->level)) {
            throw new InvalidArgumentException('The given verbosity level is not supported.', 1617549839);
        }
    }
}
