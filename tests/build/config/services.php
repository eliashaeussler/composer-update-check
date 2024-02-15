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

namespace EliasHaeussler\ComposerUpdateCheck\Tests;

use Composer\Composer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler;
use Symfony\Component\DependencyInjection;

use function dirname;

return static function (
    DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator,
    DependencyInjection\ContainerBuilder $container,
): void {
    $services = $configurator->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public()
    ;

    // Add compiler passes
    $container->addCompilerPass(new Fixtures\PublicServicesPass());

    // Prepare client
    $services->set(Handler\MockHandler::class);
    $services->set(Client::class)->factory([
        new DependencyInjection\Reference(Fixtures\ClientFactory::class),
        'make',
    ]);

    // Fix class detection of Composer service
    $services->get(Composer::class)->class(Composer::class);

    // Register fixture classes
    $fixturesPath = dirname(__DIR__, 2).'/src/Fixtures';
    $fixtures = $services->load('EliasHaeussler\\ComposerUpdateCheck\\Tests\\Fixtures\\', $fixturesPath);
    $fixtures->exclude($fixturesPath.'/PublicServicesPass.php');
};
