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

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use EliasHaeussler\ComposerUpdateCheck\Capability\UpdateCheckCommandProvider;

/**
 * Plugin
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 * @codeCoverageIgnore
 */
class Plugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->loadDependencies($composer);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Nothing to do here. Just go ahead :)
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Nothing to do here. Just go ahead :)
    }

    public function getCapabilities(): array
    {
        return [
            CommandProvider::class => UpdateCheckCommandProvider::class,
        ];
    }

    /**
     * Load required Composer dependencies.
     *
     * Loads all required Composer dependencies to make sure following code can be safely executed.
     * This is required as the main autoloader has not yed loaded required functions, but only
     * classes. As those functions are required, they have to be loaded manually.
     *
     * @param Composer $composer
     * @see https://github.com/composer/composer/issues/5998#issuecomment-269447326
     */
    private function loadDependencies(Composer $composer): void
    {
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        $autoloadFile = $vendorDir . '/autoload.php';
        if (file_exists($autoloadFile)) {
            /** @noinspection PhpIncludeInspection */
            require $vendorDir . '/autoload.php';
        }
    }
}
