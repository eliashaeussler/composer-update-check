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
 * Plugin.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @codeCoverageIgnore
 */
class Plugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->autoloadFunctions($composer);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here. Just go ahead :)
    }

    public function uninstall(Composer $composer, IOInterface $io): void
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
     * Workaround to ensure required functions from dependencies are auto-loaded.
     *
     * @see https://github.com/composer/composer/issues/4764#issuecomment-379619265
     */
    protected function autoloadFunctions(Composer $composer): void
    {
        $vendor = $composer->getConfig()->get('vendor-dir');

        $this->loadFailsafe($vendor.'/symfony/polyfill-php73/bootstrap.php');
        $this->loadFailsafe($vendor.'/symfony/polyfill-php80/bootstrap.php');
    }

    protected function loadFailsafe(string $vendorFile): void
    {
        if (file_exists($vendorFile)) {
            require_once $vendorFile;
        }
    }
}
