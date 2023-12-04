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

namespace EliasHaeussler\ComposerUpdateCheck\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Request;
use Composer\Installer;
use Composer\IO\IOInterface;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\Package;

use function array_map;
use function method_exists;

/**
 * ComposerInstaller.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final readonly class ComposerInstaller
{
    public function __construct(
        private Composer $composer,
        private IOInterface $io,
    ) {}

    public function runInstall(IOInterface $io = null): int
    {
        $io ??= $this->io;

        $preferredInstall = $this->composer->getConfig()->get('preferred-install');
        $installer = Installer::create($io, $this->composer)
            ->setPreferSource('source' === $preferredInstall)
            ->setPreferDist('dist' === $preferredInstall)
            ->setDevMode()
        ;

        if (method_exists($installer, 'setAudit')) {
            $installer->setAudit(false);
        }

        $eventDispatcher = $this->composer->getEventDispatcher();
        $eventDispatcher->setRunScripts(false);

        return $installer->run();
    }

    /**
     * @param list<Package> $packages
     */
    public function runUpdate(array $packages, IOInterface $io = null): int
    {
        $io ??= $this->io;

        $preferredInstall = $this->composer->getConfig()->get('preferred-install');
        $installer = Installer::create($io, $this->composer)
            ->setDryRun()
            ->setPreferSource('source' === $preferredInstall)
            ->setPreferDist('dist' === $preferredInstall)
            ->setDevMode()
            ->setUpdate(true)
            ->setUpdateAllowList(
                array_map(
                    static fn (Package $package) => $package->getName(),
                    $packages,
                ),
            )
            ->setUpdateAllowTransitiveDependencies(Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS)
        ;

        if (method_exists($installer, 'setAudit')) {
            $installer->setAudit(false);
        }

        return $installer->run();
    }
}
