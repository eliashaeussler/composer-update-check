<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Utility;

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

use Composer\Composer as BaseComposer;
use Composer\Console\Application;
use Composer\Json\JsonValidationException;
use EliasHaeussler\ComposerUpdateCheck\Utility\Composer;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\ExpectedCommandOutputTrait;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\TestApplicationTrait;
use EliasHaeussler\ComposerUpdateCheck\Utility\Installer;

/**
 * InstallerTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class InstallerTest extends AbstractTestCase
{
    use ExpectedCommandOutputTrait;
    use TestApplicationTrait;

    /**
     * @var BaseComposer
     */
    protected $composer;

    /**
     * @throws JsonValidationException
     */
    protected function setUp(): void
    {
        $this->goToTestDirectory();

        $application = new Application();
        $this->composer = $application->getComposer();
    }

    /**
     * @test
     */
    public function runInstallInstallsComposerDependencies(): void
    {
        $expected = 'Installing dependencies from lock file (including require-dev)';
        if (Composer::getMajorVersion() < 2) {
            $expected = 'Installing dependencies (including require-dev) from lock file';
        }

        static::assertSame(0, Installer::runInstall($this->composer));
        static::assertStringContainsString($expected, Installer::getLastOutput());
    }

    /**
     * @test
     * @dataProvider runUpdateExecutesDryRunUpdateDataProvider
     * @param array $packages
     * @param string $expected
     * @param string|null $notExpected
     */
    public function runUpdateExecutesDryRunUpdate(array $packages, string $expected, string $notExpected = null): void
    {
        // Ensure dependencies are installed
        Installer::runInstall($this->composer);

        static::assertSame(0, Installer::runUpdate($packages, $this->composer));
        static::assertStringContainsString($expected, Installer::getLastOutput());
        if ($notExpected !== null) {
            static::assertStringNotContainsString($notExpected, Installer::getLastOutput());
        }
    }

    public function runUpdateExecutesDryRunUpdateDataProvider(): array
    {
        return [
            'no explicit whitelist' => [
                [],
                $this->getExpectedCommandOutput(),
            ],
            'composer/composer only' => [
                ['composer/composer'],
                $this->getExpectedCommandOutput('composer/composer'),
                $this->getExpectedCommandOutput('phpunit/phpunit'),
            ],
            'phpunit/phpunit only' => [
                ['phpunit/phpunit'],
                $this->getExpectedCommandOutput('phpunit/phpunit'),
                $this->getExpectedCommandOutput('composer/composer'),
            ],
        ];
    }
    
    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();
        parent::tearDown();
    }
}
