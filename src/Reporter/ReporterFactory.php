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

namespace EliasHaeussler\ComposerUpdateCheck\Reporter;

use EliasHaeussler\ComposerUpdateCheck\Exception\ReporterIsNotSupported;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * ReporterFactory.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ReporterFactory
{
    /**
     * @param ServiceLocator<Reporter> $reporters
     */
    public function __construct(
        private readonly ServiceLocator $reporters,
    ) {}

    /**
     * @throws ReporterIsNotSupported
     */
    public function make(string $name): Reporter
    {
        if (!$this->reporters->has($name)) {
            throw new ReporterIsNotSupported($name);
        }

        return $this->reporters->get($name);
    }
}