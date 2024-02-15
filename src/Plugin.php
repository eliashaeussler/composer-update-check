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

namespace EliasHaeussler\ComposerUpdateCheck;

use Composer\Composer;
use Composer\IO;
use Composer\Plugin as ComposerPlugin;
use Symfony\Component\DependencyInjection as SymfonyDI;

/**
 * Plugin.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @codeCoverageIgnore
 */
final class Plugin implements ComposerPlugin\PluginInterface, ComposerPlugin\Capable, ComposerPlugin\Capability\CommandProvider
{
    private static SymfonyDI\ContainerInterface $container;

    public function __construct()
    {
        self::$container ??= (new DependencyInjection\ContainerFactory())->make();
    }

    public function activate(Composer $composer, IO\IOInterface $io): void
    {
        self::$container->set(Composer::class, $composer);
        self::$container->set(IO\IOInterface::class, $io);
    }

    public function deactivate(Composer $composer, IO\IOInterface $io): void
    {
        // Nothing to do here. Just go ahead :)
    }

    public function uninstall(Composer $composer, IO\IOInterface $io): void
    {
        // Nothing to do here. Just go ahead :)
    }

    public function getCapabilities(): array
    {
        return [
            ComposerPlugin\Capability\CommandProvider::class => self::class,
        ];
    }

    public function getCommands(): array
    {
        return [
            self::$container->get(Command\UpdateCheckCommand::class),
        ];
    }
}
