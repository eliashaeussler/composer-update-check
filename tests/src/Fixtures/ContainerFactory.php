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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Fixtures;

use Composer\Composer;
use Composer\Factory;
use Composer\IO;
use EliasHaeussler\ComposerUpdateCheck as Src;
use Symfony\Component\DependencyInjection;
use Symfony\Component\Filesystem;

use function dirname;

/**
 * ContainerFactory.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ContainerFactory
{
    public static function make(
        TestApplication $testApplication = null,
        IO\IOInterface $io = null,
    ): DependencyInjection\ContainerInterface {
        $containerFactory = new Src\DependencyInjection\ContainerFactory([
            dirname(__DIR__, 2).'/build/config/services.php',
        ]);
        $container = $containerFactory->make(true);
        $rootPath = $testApplication?->getPath() ?? dirname(__DIR__, 2);

        $io ??= new IO\BufferIO();
        $composer = Factory::create($io, Filesystem\Path::join($rootPath, 'composer.json'));

        $container->set(Composer::class, $composer);
        $container->set(IO\IOInterface::class, $io);

        return $container;
    }
}
