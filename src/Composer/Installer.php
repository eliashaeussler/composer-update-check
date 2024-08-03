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

namespace EliasHaeussler\ComposerUpdateCheck\Composer;

use Composer\Autoload;
use Composer\Composer;
use Composer\DependencyResolver;
use Composer\EventDispatcher;
use Composer\Installer as ComposerInstaller;
use Composer\IO;
use Composer\Package;
use Composer\Repository;
use EliasHaeussler\ComposerUpdateCheck\Entity;

use function array_map;
use function array_values;
use function method_exists;

/**
 * Installer.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class Installer
{
    public function __construct(
        private readonly Composer $composer,
    ) {}

    public function runInstall(IO\IOInterface $io = null): int
    {
        $io ??= new IO\NullIO();

        $composer = $this->buildComposer($io);
        $preferredInstall = $composer->getConfig()->get('preferred-install');
        $installer = ComposerInstaller::create($io, $composer)
            ->setPreferSource('source' === $preferredInstall)
            ->setPreferDist('dist' === $preferredInstall)
            ->setDevMode()
        ;

        if (method_exists($installer, 'setAudit')) {
            $installer->setAudit(false);
        }

        return $installer->run();
    }

    /**
     * @param list<Entity\Package\Package> $packages
     */
    public function runUpdate(array $packages, IO\IOInterface $io = null): Entity\Result\ComposerUpdateResult
    {
        $io ??= new IO\NullIO();

        $outdatedPackages = [];
        $allowedPackageNames = array_map(
            static fn (Entity\Package\Package $package) => $package->getName(),
            $packages,
        );

        // Inject installer logger
        $composer = $this->buildComposer($io);
        $composer->getInstallationManager()->addInstaller(
            $this->mockInstaller($allowedPackageNames, $outdatedPackages),
        );

        // Disable writing of package updates in local repository
        $composer->getRepositoryManager()->setLocalRepository(
            $this->mockLocalRepository($composer->getRepositoryManager()->getLocalRepository()),
        );

        // Disable lock file management
        $composer->getConfig()->merge([
            'config' => [
                'lock' => false,
            ],
        ]);

        $preferredInstall = $composer->getConfig()->get('preferred-install');
        $installer = ComposerInstaller::create($io, $composer)
            ->setPreferSource('source' === $preferredInstall)
            ->setPreferDist('dist' === $preferredInstall)
            ->setDevMode()
            ->setUpdate(true)
            ->setDumpAutoloader(false)
            ->setUpdateAllowList($allowedPackageNames)
            ->setUpdateAllowTransitiveDependencies(DependencyResolver\Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS)
        ;

        if (method_exists($installer, 'setAudit')) {
            $installer->setAudit(false);
        }

        return new Entity\Result\ComposerUpdateResult(
            $installer->run(),
            array_values($outdatedPackages),
        );
    }

    /**
     * @param list<non-empty-string>                                  $allowedPackageNames
     * @param array<non-empty-string, Entity\Package\OutdatedPackage> $outdatedPackages
     */
    private function mockInstaller(
        array $allowedPackageNames,
        array &$outdatedPackages,
    ): ComposerInstaller\InstallerInterface {
        return new class($allowedPackageNames, $outdatedPackages) extends ComposerInstaller\NoopInstaller {
            /**
             * @param list<non-empty-string>                                  $allowedPackageNames
             * @param array<non-empty-string, Entity\Package\OutdatedPackage> $outdatedPackages
             */
            public function __construct(
                private readonly array $allowedPackageNames,
                private array &$outdatedPackages,
            ) {}

            public function update(
                Repository\InstalledRepositoryInterface $repo,
                Package\PackageInterface $initial,
                Package\PackageInterface $target,
            ) {
                $promise = parent::update($repo, $initial, $target);
                /** @var non-empty-string $name */
                $name = $initial->getName();

                if ($this->isSuitablePackage($initial)) {
                    $this->outdatedPackages[$name] = new Entity\Package\OutdatedPackage(
                        $name,
                        new Entity\Version($initial->getPrettyVersion()),
                        new Entity\Version($target->getPrettyVersion()),
                    );
                }

                return $promise;
            }

            private function isSuitablePackage(Package\PackageInterface $package): bool
            {
                $name = $package->getName();

                if (!in_array($name, $this->allowedPackageNames, true)) {
                    return false;
                }

                return !array_key_exists($name, $this->outdatedPackages);
            }
        };
    }

    private function mockLocalRepository(
        Repository\InstalledRepositoryInterface $repository,
    ): Repository\InstalledRepositoryInterface {
        $newRepository = new Repository\InstalledArrayRepository();
        $newRepository->setDevPackageNames($repository->getDevPackageNames());

        foreach ($repository->getPackages() as $package) {
            $newPackage = clone $package;
            $newPackage->setId($package->getId());
            $newRepository->addPackage($newPackage);
        }

        return $newRepository;
    }

    private function buildComposer(IO\IOInterface $io): Composer
    {
        $composer = clone $this->composer;

        $eventDispatcher = new EventDispatcher\EventDispatcher($composer, $io);
        $eventDispatcher->setRunScripts(false);
        $composer->setEventDispatcher($eventDispatcher);

        $autoloadGenerator = new Autoload\AutoloadGenerator($composer->getEventDispatcher(), $io);
        $autoloadGenerator->setRunScripts(false);
        $composer->setAutoloadGenerator($autoloadGenerator);

        return $composer;
    }
}
