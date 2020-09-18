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
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
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
    public function executeThrowsExceptionIfNativeUpdateCommandIsNotAvailable(): void
    {
        $applicationMock = $this->getMockBuilder(Application::class)->getMock();
        $applicationMock->method('getHelperSet')->willReturn(new HelperSet());
        $applicationMock->method('getDefinition')->willReturn(new InputDefinition());
        $applicationMock->method('getComposer')->willReturn($this->composer);
        $applicationMock->method('has')->with('update')->willReturn(false);
        $command = new UpdateCheckCommand();
        $command->setApplication($applicationMock);
        $commandTester = new CommandTester($command);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1600274132);

        $commandTester->execute([]);
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
        $this->commandTester->execute(['--json' => true, '--ignore-packages' => ['composer/composer']]);

        $expected = json_encode(['status' => 'All packages are up to date.', 'skipped' => ['composer/composer']]);
        static::assertJsonStringEqualsJsonString($expected, $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executePrintsListOfOutdatedPackages(): void
    {
        $this->commandTester->execute(['--json' => true]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);
        $expectedStatus = '1 package is outdated.';

        static::assertSame($expectedStatus, $actualJson['status']);
        static::assertCount(1, $actualJson['result']);
        static::assertSame('composer/composer', $actualJson['result'][0]['Package']);
        static::assertSame('1.0.0', $actualJson['result'][0]['Outdated version']);
        static::assertNotSame('1.0.0', $actualJson['result'][0]['New version']);
    }

    private function initializeApplication(): void
    {
        $this->backedUpDirectory = getcwd();
        chdir('tests/Build');

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
