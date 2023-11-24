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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Utility;

use Composer\Console\Application;
use Composer\Json\JsonValidationException;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\ExpectedCommandOutputTrait;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\TestApplicationTrait;
use EliasHaeussler\ComposerUpdateCheck\Utility\Composer;
use EliasHaeussler\ComposerUpdateCheck\Utility\Installer;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * InstallerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class InstallerTest extends AbstractTestCase
{
    use ExpectedCommandOutputTrait;
    use TestApplicationTrait;

    private ?\Composer\Composer $composer = null;

    /**
     * @throws JsonValidationException
     */
    protected function setUp(): void
    {
        $this->goToTestDirectory();

        $application = new Application();
        $this->composer = $application->getComposer();
    }

    #[Test]
    public function runInstallInstallsComposerDependencies(): void
    {
        $expected = 'Installing dependencies from lock file (including require-dev)';
        if (Composer::getMajorVersion() < 2) {
            $expected = 'Installing dependencies (including require-dev) from lock file';
        }

        self::assertSame(0, Installer::runInstall($this->composer));
        self::assertStringContainsString($expected, Installer::getLastOutput());
    }

    /**
     * @param string[] $packages
     */
    #[Test]
    #[DataProvider('runUpdateExecutesDryRunUpdateDataProvider')]
    public function runUpdateExecutesDryRunUpdate(array $packages, string $expected, string $notExpected = null): void
    {
        // Ensure dependencies are installed
        Installer::runInstall($this->composer);

        self::assertSame(0, Installer::runUpdate($packages, $this->composer));
        self::assertStringContainsString($expected, Installer::getLastOutput());
        if (null !== $notExpected) {
            self::assertStringNotContainsString($notExpected, Installer::getLastOutput());
        }
    }

    /**
     * @return Generator<string, mixed>
     */
    public static function runUpdateExecutesDryRunUpdateDataProvider(): Generator
    {
        yield 'no explicit whitelist' => [
            [],
            self::getExpectedCommandOutput(),
        ];
        yield 'symfony/console only' => [
            ['symfony/console'],
            self::getExpectedCommandOutput('symfony/console'),
            self::getExpectedCommandOutput('symfony/http-kernel'),
        ];
        yield 'symfony/http-kernel only' => [
            ['symfony/http-kernel'],
            self::getExpectedCommandOutput('symfony/http-kernel'),
            self::getExpectedCommandOutput('symfony/console'),
        ];
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();
        parent::tearDown();
    }
}
