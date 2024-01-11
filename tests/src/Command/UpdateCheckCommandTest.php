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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Command;

use Composer\Console\Application;
use EliasHaeussler\ComposerUpdateCheck as Src;
use EliasHaeussler\ComposerUpdateCheck\Tests;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function json_encode;

/**
 * UpdateCheckCommandTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Command\UpdateCheckCommand::class)]
final class UpdateCheckCommandTest extends Framework\TestCase
{
    private Tests\Fixtures\TestApplication $testApplication;
    private ContainerInterface $container;
    private Console\Tester\CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->testApplication = Tests\Fixtures\TestApplication::normal()->boot();
        $this->createCommandTester();
    }

    #[Framework\Attributes\Test]
    public function executePrintsNoOutdatedPackagesMessageIfNoPackagesAreRequired(): void
    {
        $this->testApplication->useEmpty();
        $this->createCommandTester();

        $this->commandTester->execute([
            '--format' => 'json',
        ]);

        self::assertJsonStringEqualsJsonString(
            json_encode(['status' => 'All packages are up to date.'], JSON_THROW_ON_ERROR),
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executePrintsNoOutdatedPackagesMessageIfOutdatedPackagesAreExcluded(): void
    {
        $this->commandTester->execute([
            '--format' => 'json',
            '--exclude-packages' => ['symfony/*'],
            '--no-dev' => true,
        ]);

        self::assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'status' => 'All packages are up to date (skipped 2 packages).',
                    'excludedPackages' => ['doctrine/dbal', 'symfony/http-kernel'],
                ],
                JSON_THROW_ON_ERROR,
            ),
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executePrintsListOfOutdatedPackagesWithoutDevRequirements(): void
    {
        $this->commandTester->execute([
            '--format' => 'json',
            '--no-dev' => true,
        ]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);

        self::assertIsArray($actualJson);
        self::assertSame('1 package is outdated (skipped 1 package).', $actualJson['status']);
        self::assertCount(1, $actualJson['outdatedPackages']);

        self::assertSame('symfony/http-kernel', $actualJson['outdatedPackages'][0]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][0]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][0]['newVersion']);

        self::assertSame(['doctrine/dbal'], $actualJson['excludedPackages']);
    }

    #[Framework\Attributes\Test]
    public function executePrintsListOfOutdatedPackagesWithoutExcludedPackages(): void
    {
        $this->commandTester->execute([
            '--format' => 'json',
            '--exclude-packages' => ['symfony/http-kernel'],
        ]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);

        self::assertIsArray($actualJson);
        self::assertSame('1 package is outdated (skipped 1 package).', $actualJson['status']);
        self::assertCount(1, $actualJson['outdatedPackages']);

        self::assertSame('doctrine/dbal', $actualJson['outdatedPackages'][0]['name']);
        self::assertSame('3.1.3', $actualJson['outdatedPackages'][0]['outdatedVersion']);
        self::assertNotSame('3.1.3', $actualJson['outdatedPackages'][0]['newVersion']);

        self::assertSame(['symfony/http-kernel'], $actualJson['excludedPackages']);
    }

    #[Framework\Attributes\Test]
    public function executePrintsListOfOutdatedPackages(): void
    {
        $this->commandTester->execute([
            '--format' => 'json',
        ]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);

        self::assertIsArray($actualJson);
        self::assertSame('2 packages are outdated.', $actualJson['status']);
        self::assertCount(2, $actualJson['outdatedPackages']);

        self::assertSame('doctrine/dbal', $actualJson['outdatedPackages'][0]['name']);
        self::assertSame('3.1.3', $actualJson['outdatedPackages'][0]['outdatedVersion']);
        self::assertNotSame('3.1.3', $actualJson['outdatedPackages'][0]['newVersion']);

        self::assertSame('symfony/http-kernel', $actualJson['outdatedPackages'][1]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][1]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][1]['newVersion']);
    }

    #[Framework\Attributes\Test]
    public function executePrintsListOfOutdatedPackagesAndFlagsInsecurePackages(): void
    {
        $mockHandler = $this->container->get(MockHandler::class);
        $mockHandler->append(
            Tests\Fixtures\ResponseFactory::json('symfony-http-kernel'),
        );

        $this->commandTester->execute([
            '--format' => 'json',
            '--security-scan' => true,
        ]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);

        self::assertIsArray($actualJson);
        self::assertSame('2 packages are outdated.', $actualJson['status']);
        self::assertCount(2, $actualJson['outdatedPackages']);

        self::assertSame('doctrine/dbal', $actualJson['outdatedPackages'][0]['name']);
        self::assertSame('3.1.3', $actualJson['outdatedPackages'][0]['outdatedVersion']);
        self::assertNotSame('3.1.3', $actualJson['outdatedPackages'][0]['newVersion']);
        self::assertFalse($actualJson['outdatedPackages'][0]['insecure']);

        self::assertSame('symfony/http-kernel', $actualJson['outdatedPackages'][1]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][1]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][1]['newVersion']);
        self::assertTrue($actualJson['outdatedPackages'][1]['insecure']);
    }

    protected function tearDown(): void
    {
        $this->testApplication->shutdown();
    }

    private function createCommandTester(): void
    {
        $this->container = Tests\Fixtures\ContainerFactory::make($this->testApplication);

        $command = $this->container->get(Src\Command\UpdateCheckCommand::class);
        $application = new Application();
        $application->add($command);

        $this->commandTester = new Console\Tester\CommandTester($command);
    }
}
