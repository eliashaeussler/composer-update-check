<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020 Elias Häußler <elias@haeussler.dev>
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
 * OutdatedPackage
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class OutdatedPackage
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $outdatedVersion;

    /**
     * @var string
     */
    private $newVersion;

    public function __construct(string $name, string $outdatedVersion, string $newVersion)
    {
        $this->name = $name;
        $this->outdatedVersion = $outdatedVersion;
        $this->newVersion = $newVersion;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOutdatedVersion(): string
    {
        return $this->outdatedVersion;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }
}
