<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Command;

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
use Composer\Console\Application;
use EliasHaeussler\ComposerUpdateCheck\Command\UpdateCheckCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * UpdateCheckCommandTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UpdateCheckCommandTest extends TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var string
     */
    private $backedUpDirectory;

    protected function setUp(): void
    {
        $this->initializeApplication();
    }

    /**
     * @test
     */
    public function executePrintsNoOutdatedPackagesMessageIfNoPackagesAreRequired(): void
    {
        $this->composer->getPackage()->setRequires([]);
        $this->composer->getPackage()->setDevRequires([]);

        $this->commandTester->execute(['--json' => true]);

        $expected = json_encode(['status' => 'All packages are up to date.']);
        static::assertJsonStringEqualsJsonString($expected, $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executePrintsNoOutdatedPackagesMessageIfOutdatedPackagesAreSkipped(): void
    {
        $this->commandTester->execute(['--json' => true, '--ignore-packages' => ['composer/*'], '--no-dev' => true]);

        $expected = json_encode([
            'status' => 'All packages are up to date (skipped 2 packages).',
            'skipped' => ['phpunit/phpunit', 'composer/composer']
        ]);
        static::assertJsonStringEqualsJsonString($expected, $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executePrintsListOfOutdatedPackagesWithoutDevRequirements(): void
    {
        $this->commandTester->execute(['--json' => true, '--no-dev' => true]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);
        $expectedStatus = '1 package is outdated.';

        static::assertSame($expectedStatus, $actualJson['status']);
        static::assertCount(1, $actualJson['result']);
        static::assertSame('composer/composer', $actualJson['result'][0]['Package']);
        static::assertSame('1.0.0', $actualJson['result'][0]['Outdated version']);
        static::assertNotSame('1.0.0', $actualJson['result'][0]['New version']);
    }

    /**
     * @test
     */
    public function executePrintsListOfOutdatedPackagesWithoutSkippedPackages(): void
    {
        $this->commandTester->execute(['--json' => true, '--ignore-packages' => ['composer/composer']]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);
        $expectedStatus = '1 package is outdated.';

        static::assertSame($expectedStatus, $actualJson['status']);
        static::assertCount(1, $actualJson['result']);
        static::assertSame('phpunit/phpunit', $actualJson['result'][0]['Package']);
        static::assertSame('8.0.0', $actualJson['result'][0]['Outdated version']);
        static::assertNotSame('8.0.0', $actualJson['result'][0]['New version']);
    }

    /**
     * @test
     */
    public function executePrintsListOfOutdatedPackages(): void
    {
        $this->commandTester->execute(['--json' => true]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);
        $expectedStatus = '2 packages are outdated.';

        static::assertSame($expectedStatus, $actualJson['status']);
        static::assertCount(2, $actualJson['result']);

        static::assertSame('composer/composer', $actualJson['result'][0]['Package']);
        static::assertSame('1.0.0', $actualJson['result'][0]['Outdated version']);
        static::assertNotSame('1.0.0', $actualJson['result'][0]['New version']);

        static::assertSame('phpunit/phpunit', $actualJson['result'][1]['Package']);
        static::assertSame('8.0.0', $actualJson['result'][1]['Outdated version']);
        static::assertNotSame('8.0.0', $actualJson['result'][1]['New version']);
    }

    private function initializeApplication(): void
    {
        $this->backedUpDirectory = getcwd();
        chdir('tests/Build/test-application');

        $this->application = new Application();
        $this->application->add(new UpdateCheckCommand());
        $this->composer = $this->application->getComposer();
        $this->commandTester = new CommandTester($this->application->find('update-check'));
    }

    protected function tearDown(): void
    {
        if ($this->backedUpDirectory !== false) {
            chdir($this->backedUpDirectory);
        }
    }
}
