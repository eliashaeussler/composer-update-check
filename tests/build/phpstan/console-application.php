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

namespace EliasHaeussler\ComposerUpdateCheck;

use Composer\Composer;
use Composer\IO;
use Symfony\Component\Console;

require_once dirname(__DIR__, 3).'/vendor/autoload.php';

$container = (new DependencyInjection\ContainerFactory())->make();
$container->set(Composer::class, new Composer());
$container->set(IO\IOInterface::class, new IO\NullIO());

$application = new Console\Application();
$application->add($container->get(Command\UpdateCheckCommand::class));

return $application;
