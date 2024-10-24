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

namespace EliasHaeussler\ComposerUpdateCheck\Tests;

use Composer\Composer;
use Composer\IO;
use EliasHaeussler\ComposerUpdateCheck as Src;
use GuzzleHttp\Exception;
use GuzzleHttp\Handler;
use InvalidArgumentException;
use PHPUnit\Framework;
use Symfony\Component\Console;

/**
 * UpdateCheckerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\UpdateChecker::class)]
final class UpdateCheckerTest extends Framework\TestCase
{
    private Fixtures\TestApplication $testApplication;
    private IO\BufferIO $io;
    private Composer $composer;
    private Src\Configuration\ComposerUpdateCheckConfig $config;
    private Handler\MockHandler $mockHandler;
    private Fixtures\TestImplementations\DummyReporter $reporter;
    private Src\UpdateChecker $subject;

    protected function setUp(): void
    {
        $this->testApplication = Fixtures\TestApplication::normal()->boot();
        $this->io = new IO\BufferIO(verbosity: Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE);

        $container = Fixtures\ContainerFactory::make($this->testApplication);
        $container->set(IO\IOInterface::class, $this->io);

        $this->composer = $container->get(Composer::class);
        $this->config = new Src\Configuration\ComposerUpdateCheckConfig();
        $this->mockHandler = $container->get(Handler\MockHandler::class);
        $this->reporter = $container->get(Fixtures\TestImplementations\DummyReporter::class);
        $this->subject = $container->get(Src\UpdateChecker::class);
    }

    #[Framework\Attributes\Test]
    public function runValidatesReporters(): void
    {
        $this->config->enableReporter('foo');

        $this->expectExceptionObject(
            new Src\Exception\ReporterIsNotSupported('foo'),
        );

        $this->subject->run($this->config);
    }

    #[Framework\Attributes\Test]
    public function runValidatesReporterOptions(): void
    {
        $this->reporter->treatOptionsAsInvalid = true;

        $this->config->enableReporter('dummy');

        $this->expectException(InvalidArgumentException::class);

        $this->subject->run($this->config);
    }

    #[Framework\Attributes\Test]
    public function runReturnsEmptyUpdateCheckResultIfNoPackagesAreRequired(): void
    {
        $this->testApplication->useEmpty();

        $subject = Fixtures\ContainerFactory::make($this->testApplication)->get(Src\UpdateChecker::class);

        self::assertEquals(new Src\Entity\Result\UpdateCheckResult([]), $subject->run($this->config));
    }

    #[Framework\Attributes\Test]
    public function runThrowsExceptionIfPackagesCannotBeInstalled(): void
    {
        $this->testApplication->useErroneous();

        $container = Fixtures\ContainerFactory::make($this->testApplication);
        $container->set(IO\IOInterface::class, $this->io);

        $subject = $container->get(Src\UpdateChecker::class);
        $exception = null;

        try {
            $subject->run($this->config);
        } catch (Src\Exception\ComposerInstallFailed $exception) {
        }

        self::assertInstanceOf(Src\Exception\ComposerInstallFailed::class, $exception);
        self::assertStringContainsString(
            'The lock file is not up to date with the latest changes in composer.json.',
            $this->io->getOutput(),
        );
    }

    #[Framework\Attributes\Test]
    public function runReturnsEmptyUpdateCheckResultIfOutdatedPackagesAreSkipped(): void
    {
        $this->config->excludePackageByName('symfony/*');
        $this->config->excludeDevPackages();

        $expected = new Src\Entity\Result\UpdateCheckResult(
            [],
            [
                new Src\Entity\Package\InstalledPackage('doctrine/dbal'),
                new Src\Entity\Package\InstalledPackage('symfony/http-kernel'),
            ],
        );

        self::assertEquals($expected, $this->subject->run($this->config));

        $output = $this->io->getOutput();

        self::assertStringContainsString('📦 Resolving packages...', $output);
        self::assertStringContainsString('🚫 Skipped dev-requirements', $output);
        self::assertStringContainsString('🚫 Skipped symfony/http-kernel', $output);
    }

    #[Framework\Attributes\Test]
    public function runReturnsUpdateCheckResultWithoutDevRequirements(): void
    {
        $this->config->excludeDevPackages();

        $actual = $this->subject->run($this->config);
        $outdatedPackages = $actual->getOutdatedPackages();

        self::assertCount(1, $outdatedPackages);

        self::assertSame('symfony/http-kernel', $outdatedPackages[0]->getName());
        self::assertEquals(new Src\Entity\Version('v5.4.19'), $outdatedPackages[0]->getOutdatedVersion());
        self::assertNotEquals(new Src\Entity\Version('v5.4.19'), $outdatedPackages[0]->getNewVersion());

        $output = $this->io->getOutput();

        self::assertStringContainsString('📦 Resolving packages...', $output);
        self::assertStringContainsString('🚫 Skipped dev-requirements', $output);
    }

    #[Framework\Attributes\Test]
    public function runReturnsUpdateCheckResultWithoutSkippedPackages(): void
    {
        $this->config->excludePackageByName('symfony/http-kernel');

        $actual = $this->subject->run($this->config);
        $outdatedPackages = $actual->getOutdatedPackages();

        self::assertCount(1, $outdatedPackages);

        self::assertSame('doctrine/dbal', $outdatedPackages[0]->getName());
        self::assertEquals(new Src\Entity\Version('3.1.3'), $outdatedPackages[0]->getOutdatedVersion());
        self::assertNotEquals(new Src\Entity\Version('3.1.3'), $outdatedPackages[0]->getNewVersion());

        $output = $this->io->getOutput();

        self::assertStringContainsString('📦 Resolving packages...', $output);
        self::assertStringContainsString('🚫 Skipped symfony/http-kernel', $output);
    }

    #[Framework\Attributes\Test]
    public function runReturnsUpdateCheckResultListOfOutdatedPackages(): void
    {
        $actual = $this->subject->run($this->config);
        $outdatedPackages = $actual->getOutdatedPackages();

        self::assertCount(2, $outdatedPackages);

        self::assertSame('doctrine/dbal', $outdatedPackages[0]->getName());
        self::assertEquals(new Src\Entity\Version('3.1.3'), $outdatedPackages[0]->getOutdatedVersion());
        self::assertNotEquals(new Src\Entity\Version('3.1.3'), $outdatedPackages[0]->getNewVersion());

        self::assertSame('symfony/http-kernel', $outdatedPackages[1]->getName());
        self::assertEquals(new Src\Entity\Version('v5.4.19'), $outdatedPackages[1]->getOutdatedVersion());
        self::assertNotEquals(new Src\Entity\Version('v5.4.19'), $outdatedPackages[1]->getNewVersion());
    }

    #[Framework\Attributes\Test]
    public function runFailsOnErroneousPackagistApiResponse(): void
    {
        $this->config->performSecurityScan();

        $exception = null;

        $this->mockHandler->append(
            new Exception\TransferException('Something went wrong.'),
        );

        try {
            $this->subject->run($this->config);
        } catch (Src\Exception\UnableToFetchSecurityAdvisories $exception) {
        }

        self::assertInstanceOf(Src\Exception\UnableToFetchSecurityAdvisories::class, $exception);
        self::assertStringContainsString('Failed', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function runReturnsUpdateCheckResultListOfOutdatedPackagesAndFlagsInsecurePackages(): void
    {
        $this->config->performSecurityScan();

        $this->mockHandler->append(
            Fixtures\ResponseFactory::json('symfony-http-kernel'),
        );

        $actual = $this->subject->run($this->config);
        $outdatedPackages = $actual->getOutdatedPackages();

        self::assertCount(2, $outdatedPackages);

        self::assertSame('doctrine/dbal', $outdatedPackages[0]->getName());
        self::assertEquals(new Src\Entity\Version('3.1.3'), $outdatedPackages[0]->getOutdatedVersion());
        self::assertNotEquals(new Src\Entity\Version('3.1.3'), $outdatedPackages[0]->getNewVersion());
        self::assertFalse($outdatedPackages[0]->isInsecure());

        self::assertSame('symfony/http-kernel', $outdatedPackages[1]->getName());
        self::assertEquals(new Src\Entity\Version('v5.4.19'), $outdatedPackages[1]->getOutdatedVersion());
        self::assertNotEquals(new Src\Entity\Version('v5.4.19'), $outdatedPackages[1]->getNewVersion());
        self::assertTrue($outdatedPackages[1]->isInsecure());

        self::assertStringContainsString('🚨 Looking up security advisories...', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function runDispatchesPostUpdateCheckEvent(): void
    {
        $this->mockHandler->append(
            Fixtures\ResponseFactory::json('symfony-http-kernel'),
        );

        $this->config->excludePackageByName('doctrine/dbal');
        $this->config->performSecurityScan();

        $listener = static function (Src\Event\PostUpdateCheckEvent $event): void {
            $outdatedPackages = $event->getUpdateCheckResult()->getOutdatedPackages();

            self::assertCount(1, $outdatedPackages);

            self::assertSame('symfony/http-kernel', $outdatedPackages[0]->getName());
            self::assertEquals(new Src\Entity\Version('v5.4.19'), $outdatedPackages[0]->getOutdatedVersion());
            self::assertNotEquals(new Src\Entity\Version('v5.4.19'), $outdatedPackages[0]->getNewVersion());
            self::assertTrue($outdatedPackages[0]->isInsecure());
        };

        $this->composer->getEventDispatcher()->addListener(Src\Event\PostUpdateCheckEvent::NAME, $listener);

        $this->subject->run($this->config);
    }

    #[Framework\Attributes\Test]
    public function runReportsOutdatedPackages(): void
    {
        $this->config->enableReporter('dummy', ['foo' => 'baz']);

        $actual = $this->subject->run($this->config);
        $reportedResults = $this->reporter->reportedResults;

        self::assertCount(1, $reportedResults);
        self::assertSame($actual, $reportedResults[0]['result']);
        self::assertSame(['foo' => 'baz'], $reportedResults[0]['options']);
    }

    protected function tearDown(): void
    {
        $this->testApplication->shutdown();
    }
}
