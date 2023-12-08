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

namespace EliasHaeussler\ComposerUpdateCheck\Configuration\Adapter;

use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use EliasHaeussler\ComposerUpdateCheck\Exception\FileDoesNotExist;
use Symfony\Component\Filesystem\Path;

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
    protected readonly TreeMapper $mapper;

    /**
     * @throws FileDoesNotExist
     */
    public function __construct(string $filename)
    {
        $this->filename = $this->resolveFilename($filename);
        $this->mapper = $this->createMapper();
    }

    /**
     * @throws FileDoesNotExist
     */
    private function resolveFilename(string $filename): string
    {
        if (!Path::isAbsolute($filename)) {
            $currentWorkingDirectory = (string) getcwd();
            $filename = Path::join($currentWorkingDirectory, $filename);
        }

        if (!file_exists($filename)) {
            throw new FileDoesNotExist($filename);
        }

        return $filename;
    }

    private function createMapper(): TreeMapper
    {
        return (new MapperBuilder())
            ->allowPermissiveTypes()
            ->mapper()
        ;
    }
}