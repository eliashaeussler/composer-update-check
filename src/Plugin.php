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
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Semver\Constraint\Constraint;
use EliasHaeussler\ComposerUpdateCheck\Capability\UpdateCheckCommandProvider;

/**
 * Plugin
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 * @codeCoverageIgnore
 */
class Plugin implements PluginInterface, Capable, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here. Just go ahead :)
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

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => [
                ['checkPossibleIncompatibilities'],
            ],
        ];
    }

    /**
     * Check for possible incompatibilities of currently installed packages.
     *
     * Tests whether some specific packages are currently installed and if the installed versions
     * might be incompatible with the current environment. For example, this can be the case for
     * the package "composer/semver" in combination with Composer 1.x.
     *
     * @param Event $event
     */
    public function checkPossibleIncompatibilities(Event $event): void
    {
        $packageManager = new Utility\PackageManager($event->getComposer(), $event->getIO());
        $constraint = new Constraint('>=', '2.0.0');
        if (
            $packageManager->isPackageInstalled('composer/semver', $constraint)
            && Utility\Composer::getMajorVersion() < 2
        ) {
            $packageManager->suggestRequirement('composer/semver', '^1.0');
        }
    }
}
