<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2024 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\ComposerUpdateCheck\Configuration\Adapter;

use CuyZ\Valinor;
use EliasHaeussler\ComposerUpdateCheck\Exception;
use Symfony\Component\Filesystem;

use function file_exists;
use function getcwd;

/**
 * FileBasedConfigAdapter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
abstract class FileBasedConfigAdapter implements ConfigAdapter
{
    protected readonly string $filename;
    protected readonly Valinor\Mapper\TreeMapper $mapper;

    /**
     * @throws Exception\FileDoesNotExist
     */
    public function __construct(string $filename)
    {
        $this->filename = $this->resolveFilename($filename);
        $this->mapper = $this->createMapper();
    }

    /**
     * @throws Exception\FileDoesNotExist
     */
    private function resolveFilename(string $filename): string
    {
        if (!Filesystem\Path::isAbsolute($filename)) {
            $currentWorkingDirectory = (string) getcwd();
            $filename = Filesystem\Path::join($currentWorkingDirectory, $filename);
        }

        if (!file_exists($filename)) {
            throw new Exception\FileDoesNotExist($filename);
        }

        return $filename;
    }

    private function createMapper(): Valinor\Mapper\TreeMapper
    {
        return (new Valinor\MapperBuilder())
            ->allowPermissiveTypes()
            ->mapper()
        ;
    }
}
