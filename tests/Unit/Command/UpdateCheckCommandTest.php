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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Command;

use Composer\Console\Application;
use EliasHaeussler\ComposerUpdateCheck\Command\UpdateCheckCommand;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\TestApplicationTrait;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * UpdateCheckCommandTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateCheckCommandTest extends AbstractTestCase
{
    use TestApplicationTrait;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->goToTestDirectory();

        $application = new Application();
        $application->add(new UpdateCheckCommand());

        $this->commandTester = new CommandTester($application->find('update-check'));
    }

    #[Test]
    public function executePrintsNoOutdatedPackagesMessageIfNoPackagesAreRequired(): void
    {
        $this->goToTestDirectory(self::TEST_APPLICATION_EMPTY);

        $this->commandTester->execute(['--json' => true]);

        $expected = json_encode(['status' => 'All packages are up to date.']);
        self::assertJsonStringEqualsJsonString($expected, $this->commandTester->getDisplay());
    }

    #[Test]
    public function executePrintsNoOutdatedPackagesMessageIfOutdatedPackagesAreSkipped(): void
    {
        $this->commandTester->execute(['--json' => true, '--ignore-packages' => ['symfony/*'], '--no-dev' => true]);

        $expected = json_encode([
            'status' => 'All packages are up to date (skipped 3 packages).',
            'skipped' => ['codeception/codeception', 'symfony/console', 'symfony/http-kernel'],
        ]);
        self::assertJsonStringEqualsJsonString($expected, $this->commandTester->getDisplay());
    }

    #[Test]
    public function executePrintsListOfOutdatedPackagesWithoutDevRequirements(): void
    {
        $this->commandTester->execute(['--json' => true, '--no-dev' => true]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);
        $expectedStatus = '2 packages are outdated.';

        self::assertSame($expectedStatus, $actualJson['status']);
        self::assertCount(2, $actualJson['result']);

        self::assertSame('symfony/console', $actualJson['result'][0]['Package']);
        self::assertSame('v4.4.9', $actualJson['result'][0]['Outdated version']);
        self::assertNotSame('v4.4.9', $actualJson['result'][0]['New version']);

        self::assertSame('symfony/http-kernel', $actualJson['result'][1]['Package']);
        self::assertSame('v4.4.9', $actualJson['result'][1]['Outdated version']);
        self::assertNotSame('v4.4.9', $actualJson['result'][1]['New version']);
    }

    #[Test]
    public function executePrintsListOfOutdatedPackagesWithoutSkippedPackages(): void
    {
        $this->commandTester->execute(['--json' => true, '--ignore-packages' => ['symfony/console']]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);
        $expectedStatus = '2 packages are outdated.';

        self::assertSame($expectedStatus, $actualJson['status']);
        self::assertCount(2, $actualJson['result']);

        self::assertSame('codeception/codeception', $actualJson['result'][0]['Package']);
        self::assertSame('4.1.9', $actualJson['result'][0]['Outdated version']);
        self::assertNotSame('4.1.9', $actualJson['result'][0]['New version']);

        self::assertSame('symfony/http-kernel', $actualJson['result'][1]['Package']);
        self::assertSame('v4.4.9', $actualJson['result'][1]['Outdated version']);
        self::assertNotSame('v4.4.9', $actualJson['result'][1]['New version']);
    }

    #[Test]
    public function executePrintsListOfOutdatedPackages(): void
    {
        $this->commandTester->execute(['--json' => true]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);
        $expectedStatus = '3 packages are outdated.';

        self::assertSame($expectedStatus, $actualJson['status']);
        self::assertCount(3, $actualJson['result']);

        self::assertSame('codeception/codeception', $actualJson['result'][0]['Package']);
        self::assertSame('4.1.9', $actualJson['result'][0]['Outdated version']);
        self::assertNotSame('4.1.9', $actualJson['result'][0]['New version']);

        self::assertSame('symfony/console', $actualJson['result'][1]['Package']);
        self::assertSame('v4.4.9', $actualJson['result'][1]['Outdated version']);
        self::assertNotSame('v4.4.9', $actualJson['result'][1]['New version']);

        self::assertSame('symfony/http-kernel', $actualJson['result'][2]['Package']);
        self::assertSame('v4.4.9', $actualJson['result'][2]['Outdated version']);
        self::assertNotSame('v4.4.9', $actualJson['result'][2]['New version']);
    }

    #[Test]
    public function executePrintsListOfOutdatedPackagesAndFlagsInsecurePackages(): void
    {
        $this->commandTester->execute(['--json' => true, '--security-scan' => true]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);
        $expectedStatus = '3 packages are outdated.';

        self::assertSame($expectedStatus, $actualJson['status']);
        self::assertCount(3, $actualJson['result']);

        self::assertSame('codeception/codeception', $actualJson['result'][0]['Package']);
        self::assertSame('4.1.9', $actualJson['result'][0]['Outdated version']);
        self::assertNotSame('4.1.9', $actualJson['result'][0]['New version']);
        self::assertTrue($actualJson['result'][0]['Insecure']);

        self::assertSame('symfony/console', $actualJson['result'][1]['Package']);
        self::assertSame('v4.4.9', $actualJson['result'][1]['Outdated version']);
        self::assertNotSame('v4.4.9', $actualJson['result'][1]['New version']);
        self::assertFalse($actualJson['result'][1]['Insecure']);

        self::assertSame('symfony/http-kernel', $actualJson['result'][2]['Package']);
        self::assertSame('v4.4.9', $actualJson['result'][2]['Outdated version']);
        self::assertNotSame('v4.4.9', $actualJson['result'][2]['New version']);
        self::assertTrue($actualJson['result'][2]['Insecure']);
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();
        parent::tearDown();
    }
}
