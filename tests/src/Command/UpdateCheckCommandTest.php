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
use Composer\IO;
use EliasHaeussler\ComposerUpdateCheck as Src;
use EliasHaeussler\ComposerUpdateCheck\Tests;
use Generator;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection;

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
    private IO\BufferIO $io;
    private DependencyInjection\ContainerInterface $container;
    private Console\Tester\CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->testApplication = Tests\Fixtures\TestApplication::normal()->boot();
        $this->io = new IO\BufferIO(verbosity: Console\Output\OutputInterface::VERBOSITY_VERBOSE);

        $this->createCommandTester();
    }

    #[Framework\Attributes\Test]
    public function executeFailsOnUnsupportedConfigFile(): void
    {
        $config = Tests\Fixtures\ConfigProvider::json('unsupported-config');
        $actual = $this->commandTester->execute([
            '--config' => $config,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $actual);
        self::assertStringContainsString(
            'Invalid source, expected an iterable.',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeFailsOnConfigMappingErrors(): void
    {
        $config = Tests\Fixtures\ConfigProvider::json('invalid-config');
        $actual = $this->commandTester->execute([
            '--config' => $config,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $actual);
        self::assertMatchesRegularExpression(
            '/format: Value .+ is not a valid string\./',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeResolvesRelativeConfigFilePath(): void
    {
        $this->testApplication
            ->withConfig(
                Tests\Fixtures\ConfigProvider::php('valid-config'),
                'composer-update-check.php',
            )
            ->reboot()
        ;

        $this->commandTester->execute(
            [
                '--config' => 'composer-update-check.php',
            ],
            [
                'verbosity' => Console\Output\OutputInterface::VERBOSITY_VERBOSE,
            ],
        );

        self::assertStringContainsString('🚫 Skipped symfony/http-kernel', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('executeUsesConfigFromGivenConfigFileDataProvider')]
    public function executeUsesConfigFromGivenConfigFile(string $configFile): void
    {
        $this->commandTester->execute(
            [
                '--config' => $configFile,
            ],
            [
                'verbosity' => Console\Output\OutputInterface::VERBOSITY_VERBOSE,
            ],
        );

        self::assertStringContainsString('🚫 Skipped "symfony/http-kernel"', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function executeOverwritesConfigFromGivenFileWithCommandOptions(): void
    {
        $this->commandTester->execute(
            [
                '--config' => Tests\Fixtures\ConfigProvider::php('valid-config'),
                '--format' => Src\IO\Formatter\GitHubFormatter::FORMAT,
            ],
        );

        self::assertStringContainsString(
            '::warning file=composer.json,title=Package doctrine/dbal is outdated',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeOverwritesConfigFromGivenFileDefinedAsEnvironmentVariable(): void
    {
        $configFile = Tests\Fixtures\ConfigProvider::php('valid-config');

        putenv('COMPOSER_UPDATE_CHECK_CONFIG='.$configFile);

        $this->commandTester->execute([
            '--format' => Src\IO\Formatter\GitHubFormatter::FORMAT,
        ]);

        putenv('COMPOSER_UPDATE_CHECK_CONFIG');

        self::assertStringContainsString(
            '::warning file=composer.json,title=Package doctrine/dbal is outdated',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeRespectsConfigFileDefinedAsEnvironmentVariableAndCommandOption(): void
    {
        $configFile = Tests\Fixtures\ConfigProvider::php('valid-config');

        putenv('COMPOSER_UPDATE_CHECK_CONFIG='.$configFile);

        $this->commandTester->execute([
            '--config' => Tests\Fixtures\ConfigProvider::php('valid-config-exclude-symfony'),
            '--exclude' => [
                'doctrine/dbal',
            ],
        ]);

        putenv('COMPOSER_UPDATE_CHECK_CONFIG');

        self::assertStringContainsString('🚫 Skipped "doctrine/dbal"', $this->io->getOutput());
        self::assertStringContainsString('🚫 Skipped "symfony/config"', $this->io->getOutput());
        self::assertStringContainsString('🚫 Skipped "symfony/http-kernel"', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function executeOverwritesConfigFromGivenFileAndCommandOptionsWithEnvironmentVariables(): void
    {
        putenv('COMPOSER_UPDATE_CHECK_FORMAT='.Src\IO\Formatter\GitHubFormatter::FORMAT);

        $this->commandTester->execute([
            '--config' => Tests\Fixtures\ConfigProvider::php('valid-config'),
            '--format' => Src\IO\Formatter\GitLabFormatter::FORMAT,
        ]);

        putenv('COMPOSER_UPDATE_CHECK_FORMAT');

        self::assertStringContainsString(
            '::warning file=composer.json,title=Package doctrine/dbal is outdated',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executePrintsNoOutdatedPackagesMessageIfOutdatedPackagesAreExcluded(): void
    {
        $this->commandTester->execute([
            '--format' => 'json',
            '--exclude' => ['symfony/*'],
            '--no-dev' => true,
        ]);

        self::assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'status' => 'All packages are up to date (skipped 3 packages).',
                    'excludedPackages' => ['doctrine/dbal', 'symfony/config', 'symfony/http-kernel'],
                ],
                JSON_THROW_ON_ERROR,
            ),
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executePrintsListOfOutdatedPackagesWithoutExcludedPackages(): void
    {
        $this->commandTester->execute([
            '--format' => 'json',
            '--exclude' => ['symfony/http-kernel'],
        ]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);

        self::assertIsArray($actualJson);
        self::assertSame('2 packages are outdated (skipped 1 package).', $actualJson['status']);
        self::assertCount(2, $actualJson['outdatedPackages']);

        self::assertSame('doctrine/dbal', $actualJson['outdatedPackages'][0]['name']);
        self::assertSame('3.1.3', $actualJson['outdatedPackages'][0]['outdatedVersion']);
        self::assertNotSame('3.1.3', $actualJson['outdatedPackages'][0]['newVersion']);

        self::assertSame('symfony/config', $actualJson['outdatedPackages'][1]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][1]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][1]['newVersion']);

        self::assertSame(['symfony/http-kernel'], $actualJson['excludedPackages']);
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
        self::assertSame('2 packages are outdated (skipped 1 package).', $actualJson['status']);
        self::assertCount(2, $actualJson['outdatedPackages']);

        self::assertSame('symfony/config', $actualJson['outdatedPackages'][0]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][0]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][0]['newVersion']);

        self::assertSame('symfony/http-kernel', $actualJson['outdatedPackages'][1]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][1]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][1]['newVersion']);

        self::assertSame(['doctrine/dbal'], $actualJson['excludedPackages']);
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
        self::assertSame('3 packages are outdated.', $actualJson['status']);
        self::assertCount(3, $actualJson['outdatedPackages']);

        self::assertSame('doctrine/dbal', $actualJson['outdatedPackages'][0]['name']);
        self::assertSame('3.1.3', $actualJson['outdatedPackages'][0]['outdatedVersion']);
        self::assertNotSame('3.1.3', $actualJson['outdatedPackages'][0]['newVersion']);
        self::assertFalse($actualJson['outdatedPackages'][0]['insecure']);

        self::assertSame('symfony/config', $actualJson['outdatedPackages'][1]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][1]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][1]['newVersion']);
        self::assertFalse($actualJson['outdatedPackages'][1]['insecure']);

        self::assertSame('symfony/http-kernel', $actualJson['outdatedPackages'][2]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][2]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][2]['newVersion']);
        self::assertTrue($actualJson['outdatedPackages'][2]['insecure']);
    }

    #[Framework\Attributes\Test]
    public function executeReportsUpdateCheckResultUsingConfiguredReporter(): void
    {
        $reporter = $this->container->get(Tests\Fixtures\TestImplementations\DummyReporter::class);

        $this->commandTester->execute([
            '--reporter' => ['dummy:{"foo":"baz"}'],
        ]);

        self::assertCount(1, $reporter->reportedResults);

        $report = $reporter->reportedResults[0]['result'];
        $options = $reporter->reportedResults[0]['options'];

        self::assertSame(['foo' => 'baz'], $options);

        self::assertSame('doctrine/dbal', $report->getOutdatedPackages()[0]->getName());
        self::assertEquals(new Src\Entity\Version('3.1.3'), $report->getOutdatedPackages()[0]->getOutdatedVersion());
        self::assertNotEquals(new Src\Entity\Version('3.1.3'), $report->getOutdatedPackages()[0]->getNewVersion());

        self::assertSame('symfony/config', $report->getOutdatedPackages()[1]->getName());
        self::assertEquals(new Src\Entity\Version('v5.4.19'), $report->getOutdatedPackages()[1]->getOutdatedVersion());
        self::assertNotEquals(new Src\Entity\Version('v5.4.19'), $report->getOutdatedPackages()[1]->getNewVersion());

        self::assertSame('symfony/http-kernel', $report->getOutdatedPackages()[2]->getName());
        self::assertEquals(new Src\Entity\Version('v5.4.19'), $report->getOutdatedPackages()[2]->getOutdatedVersion());
        self::assertNotEquals(new Src\Entity\Version('v5.4.19'), $report->getOutdatedPackages()[2]->getNewVersion());
    }

    #[Framework\Attributes\Test]
    public function executeDisablesGivenReportersEvenIfTheyWerePreviouslyEnabled(): void
    {
        $reporter = $this->container->get(Tests\Fixtures\TestImplementations\DummyReporter::class);
        $reporter2 = $this->container->get(Tests\Fixtures\TestImplementations\Dummy2Reporter::class);

        $this->commandTester->execute([
            '--reporter' => ['dummy', 'dummy-2'],
            '--disable-reporter' => ['dummy'],
        ]);

        self::assertCount(0, $reporter->reportedResults);
        self::assertCount(1, $reporter2->reportedResults);
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
    public function executePrintsListOfOutdatedPackages(): void
    {
        $this->commandTester->execute([
            '--format' => 'json',
        ]);

        $actualJson = json_decode($this->commandTester->getDisplay(), true);

        self::assertIsArray($actualJson);
        self::assertSame('3 packages are outdated.', $actualJson['status']);
        self::assertCount(3, $actualJson['outdatedPackages']);

        self::assertSame('doctrine/dbal', $actualJson['outdatedPackages'][0]['name']);
        self::assertSame('3.1.3', $actualJson['outdatedPackages'][0]['outdatedVersion']);
        self::assertNotSame('3.1.3', $actualJson['outdatedPackages'][0]['newVersion']);

        self::assertSame('symfony/config', $actualJson['outdatedPackages'][1]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][1]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][1]['newVersion']);

        self::assertSame('symfony/http-kernel', $actualJson['outdatedPackages'][2]['name']);
        self::assertSame('v5.4.19', $actualJson['outdatedPackages'][2]['outdatedVersion']);
        self::assertNotSame('v5.4.19', $actualJson['outdatedPackages'][2]['newVersion']);
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function executeUsesConfigFromGivenConfigFileDataProvider(): Generator
    {
        yield 'json' => [Tests\Fixtures\ConfigProvider::json('valid-config')];
        yield 'php' => [Tests\Fixtures\ConfigProvider::php('valid-config')];
        yield 'yaml' => [Tests\Fixtures\ConfigProvider::yaml('valid-config')];
        yield 'yml' => [Tests\Fixtures\ConfigProvider::yml('valid-config')];
    }

    protected function tearDown(): void
    {
        $this->testApplication->shutdown();
    }

    private function createCommandTester(): void
    {
        $this->container = Tests\Fixtures\ContainerFactory::make($this->testApplication, $this->io);

        $command = $this->container->get(Src\Command\UpdateCheckCommand::class);
        $application = new Application();
        $application->add($command);

        $this->commandTester = new Console\Tester\CommandTester($command);
    }
}
