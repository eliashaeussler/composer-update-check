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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Composer;

use Composer\IO;
use EliasHaeussler\ComposerUpdateCheck as Src;
use EliasHaeussler\ComposerUpdateCheck\Tests;
use Generator;
use PHPUnit\Framework;

use function count;

/**
 * InstallerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Composer\Installer::class)]
final class InstallerTest extends Framework\TestCase
{
    private Tests\Fixtures\TestApplication $testApplication;
    private IO\BufferIO $io;
    private Src\Composer\Installer $subject;

    protected function setUp(): void
    {
        $this->testApplication = Tests\Fixtures\TestApplication::normal()->boot();

        $container = Tests\Fixtures\ContainerFactory::make($this->testApplication);

        $this->io = new IO\BufferIO();
        $this->subject = $container->get(Src\Composer\Installer::class);
    }

    #[Framework\Attributes\Test]
    public function runInstallInstallsComposerDependencies(): void
    {
        $expected = 'Installing dependencies from lock file (including require-dev)';

        self::assertSame(0, $this->subject->runInstall($this->io));
        self::assertStringContainsString($expected, $this->io->getOutput());
    }

    /**
     * @param list<Src\Entity\Package\Package> $packages
     * @param list<non-empty-string>           $expected
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('runUpdateExecutesDryRunUpdateDataProvider')]
    public function runUpdateExecutesDryRunUpdate(array $packages, array $expected): void
    {
        // Ensure dependencies are installed
        $this->subject->runInstall($this->io);

        $actual = $this->subject->runUpdate($packages, $this->io);

        self::assertEquals(0, $actual->getExitCode());
        self::assertTrue($actual->isSuccessful());
        self::assertCount(count($expected), $actual->getOutdatedPackages());

        foreach ($expected as $i => $package) {
            self::assertSame($package, $actual->getOutdatedPackages()[$i]->getName());
        }
    }

    /**
     * @return Generator<string, array{list<Src\Entity\Package\Package>, list<non-empty-string>}>
     */
    public static function runUpdateExecutesDryRunUpdateDataProvider(): Generator
    {
        yield 'no explicit whitelist' => [
            [],
            [],
        ];
        yield 'doctrine/dbal only' => [
            [
                new Src\Entity\Package\InstalledPackage('doctrine/dbal'),
            ],
            [
                'doctrine/dbal',
            ],
        ];
        yield 'symfony/http-kernel only' => [
            [
                new Src\Entity\Package\InstalledPackage('symfony/http-kernel'),
            ],
            [
                'symfony/http-kernel',
            ],
        ];
    }

    protected function tearDown(): void
    {
        $this->testApplication->shutdown();
    }
}
