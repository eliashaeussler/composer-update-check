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

use EliasHaeussler\RectorConfig\Config\Config;
use EliasHaeussler\RectorConfig\Entity\Version;
use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Symfony\Symfony42\Rector\MethodCall\ContainerGetToConstructorInjectionRector;
use Rector\Symfony\Symfony53\Rector\Class_\CommandDescriptionToPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    Config::create($rectorConfig, PhpVersion::PHP_81)
        ->in(
            __DIR__.'/src',
            __DIR__.'/tests/src',
        )
        ->withSymfony(Version::createMinor(5, 4))
        ->withPHPUnit()
        ->skip(
            AnnotationToAttributeRector::class,
            [
                __DIR__.'/src/DependencyInjection/CompilerPass/ContainerBuilderDebugDumpPass.php',
                __DIR__.'/src/Event/PostUpdateCheckEvent.php',
                __DIR__.'/src/Plugin.php',
            ],
        )
        ->skip(
            CommandDescriptionToPropertyRector::class,
            [
                __DIR__.'/src/Command/UpdateCheckCommand.php',
            ],
        )
        ->skip(
            ContainerGetToConstructorInjectionRector::class,
            [
                __DIR__.'/src/Plugin.php',
            ],
        )
        ->apply()
    ;
};
