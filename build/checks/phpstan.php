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

use EliasHaeussler\ComposerUpdateCheck\DependencyInjection;
use EliasHaeussler\PHPStanConfig;

$rootPath = dirname(__DIR__, 2);

return PHPStanConfig\Config\Config::create($rootPath)
    ->in(
        'src',
        'tests/src',
    )
    ->with('vendor/cuyz/valinor/qa/PHPStan/valinor-phpstan-suppress-pure-errors.php')
    ->withBaseline(__DIR__.'/phpstan-baseline.neon')
    ->maxLevel()
    ->withSet(static function (PHPStanConfig\Set\SymfonySet $set) use ($rootPath) {
        $containerFactory = new DependencyInjection\ContainerFactory([$rootPath.'/tests/build/config/services.php']);
        $containerXmlFile = $containerFactory->make(true)->getParameter('debug.container_xml_filename');

        $set->withConsoleApplicationLoader(__DIR__.'/console-application.php');
        $set->withContainerXmlPath($containerXmlFile);
    })
    ->toArray()
;
