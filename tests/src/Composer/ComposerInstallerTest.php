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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Composer;

use Composer\IO;
use EliasHaeussler\ComposerUpdateCheck as Src;
use EliasHaeussler\ComposerUpdateCheck\Tests;
use Generator;
use PHPUnit\Framework;

/**
 * ComposerInstallerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Composer\ComposerInstaller::class)]
final class ComposerInstallerTest extends Framework\TestCase
{
    private Tests\Fixtures\TestApplication $testApplication;
    private IO\BufferIO $io;
    private Src\Composer\ComposerInstaller $subject;

    protected function setUp(): void
    {
        $this->testApplication = Tests\Fixtures\TestApplication::normal()->boot();

        $container = Tests\Fixtures\ContainerFactory::make($this->testApplication);
        $io = $container->get(IO\IOInterface::class);

        self::assertInstanceOf(IO\BufferIO::class, $io);

        $this->io = $io;
        $this->subject = $container->get(Src\Composer\ComposerInstaller::class);
    }

    #[Framework\Attributes\Test]
    public function runInstallInstallsComposerDependencies(): void
    {
        $expected = 'Installing dependencies from lock file (including require-dev)';

        self::assertSame(0, $this->subject->runInstall());
        self::assertStringContainsString($expected, $this->io->getOutput());
    }

    /**
     * @param list<Src\Entity\Package\Package> $packages
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('runUpdateExecutesDryRunUpdateDataProvider')]
    public function runUpdateExecutesDryRunUpdate(array $packages, string $expected, ?string $notExpected): void
    {
        // Ensure dependencies are installed
        $this->subject->runInstall();

        self::assertSame(0, $this->subject->runUpdate($packages));
        self::assertStringContainsString($expected, $this->io->getOutput());

        if (null !== $notExpected) {
            self::assertStringNotContainsString($notExpected, $this->io->getOutput());
        }
    }

    /**
     * @return Generator<string, array{list<Src\Entity\Package\Package>, string, string|null}>
     */
    public static function runUpdateExecutesDryRunUpdateDataProvider(): Generator
    {
        yield 'no explicit whitelist' => [
            [],
            '- Upgrading',
            null,
        ];
        yield 'doctrine/dbal only' => [
            [
                new Src\Entity\Package\InstalledPackage('doctrine/dbal'),
            ],
            '- Upgrading doctrine/dbal',
            '- Upgrading symfony/http-kernel',
        ];
        yield 'symfony/http-kernel only' => [
            [
                new Src\Entity\Package\InstalledPackage('symfony/http-kernel'),
            ],
            '- Upgrading symfony/http-kernel',
            '- Upgrading symfony/console',
        ];
    }

    protected function tearDown(): void
    {
        $this->testApplication->shutdown();
    }
}
