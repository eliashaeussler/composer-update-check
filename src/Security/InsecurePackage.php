<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Security;

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
 * InsecurePackage
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class InsecurePackage
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $affectedVersions;

    /**
     * @param string $name
     * @param string[] $affectedVersions
     */
    public function __construct(string $name, array $affectedVersions)
    {
        $this->name = $name;
        $this->affectedVersions = $affectedVersions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getAffectedVersions(): array
    {
        return $this->affectedVersions;
    }

    /**
     * @param string[] $affectedVersions
     * @return self
     */
    public function setAffectedVersions(array $affectedVersions): self
    {
        $this->affectedVersions = $affectedVersions;
        return $this;
    }
}
