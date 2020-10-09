<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit;

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
use Composer\Json\JsonValidationException;
use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\UpdateChecker;

/**
 * UpdateCheckerTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UpdateCheckerTest extends AbstractTestCase
{
    use TestApplicationTrait;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var UpdateChecker
     */
    protected $subject;

    /**
     * @throws JsonValidationException
     */
    protected function setUp(): void
    {
        $this->goToTestDirectory();

        $application = new Application();
        $this->composer = $application->getComposer();
        $this->subject = new UpdateChecker($this->composer);
    }

    /**
     * @test
     * @throws JsonValidationException
     */
    public function runThrowsExceptionIfDependenciesCannotBeInstalled(): void
    {
        $this->goToTestDirectory(self::TEST_APPLICATION_ERRONEOUS);

        $composer = (new Application())->getComposer();
        $subject = new UpdateChecker($composer);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1600278536);

        $subject->run();
    }

    /**
     * @test
     */
    public function runReturnsEmptyUpdateCheckResultIfNoPackagesAreRequired(): void
    {
        $this->goToTestDirectory(self::TEST_APPLICATION_EMPTY);

        $expected = new UpdateCheckResult([]);
        static::assertEquals($expected, $this->subject->run());
    }

    /**
     * @test
     */
    public function runReturnsEmptyUpdateCheckResultIfOutdatedPackagesAreSkipped(): void
    {
        $expected = new UpdateCheckResult([]);
        static::assertEquals($expected, $this->subject->run(['composer/*'], false));
    }

    /**
     * @test
     */
    public function runReturnsUpdateCheckResultWithoutDevRequirements(): void
    {
        $outdatedPackages = $this->subject->run([], false)->getOutdatedPackages();
        /** @var OutdatedPackage $outdatedPackage */
        $outdatedPackage = reset($outdatedPackages);

        static::assertCount(1, $outdatedPackages);
        static::assertSame('composer/composer', $outdatedPackage->getName());
        static::assertSame('1.0.0', $outdatedPackage->getOutdatedVersion());
        static::assertNotSame('1.0.0', $outdatedPackage->getNewVersion());
    }

    /**
     * @test
     */
    public function runReturnsUpdateCheckResultWithoutSkippedPackages(): void
    {
        $outdatedPackages = $this->subject->run(['composer/*'])->getOutdatedPackages();
        /** @var OutdatedPackage $outdatedPackage */
        $outdatedPackage = reset($outdatedPackages);

        static::assertCount(1, $outdatedPackages);
        static::assertSame('phpunit/phpunit', $outdatedPackage->getName());
        static::assertSame('5.0.10', $outdatedPackage->getOutdatedVersion());
        static::assertNotSame('5.0.10', $outdatedPackage->getNewVersion());
    }

    /**
     * @test
     */
    public function runReturnsUpdateCheckResultListOfOutdatedPackages(): void
    {
        $outdatedPackages = $this->subject->run()->getOutdatedPackages();
        /** @var OutdatedPackage $firstOutdatedPackage */
        $firstOutdatedPackage = reset($outdatedPackages);
        /** @var OutdatedPackage $secondOutdatedPackage */
        $secondOutdatedPackage = next($outdatedPackages);

        static::assertCount(2, $outdatedPackages);

        static::assertSame('composer/composer', $firstOutdatedPackage->getName());
        static::assertSame('1.0.0', $firstOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('1.0.0', $firstOutdatedPackage->getNewVersion());

        static::assertSame('phpunit/phpunit', $secondOutdatedPackage->getName());
        static::assertSame('5.0.10', $secondOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('5.0.10', $secondOutdatedPackage->getNewVersion());
    }

    /**
     * @test
     */
    public function runReturnsUpdateCheckResultListOfOutdatedPackagesAndFlagsInsecurePackages(): void
    {
        $this->subject->setSecurityScan(true);
        $outdatedPackages = $this->subject->run()->getOutdatedPackages();
        /** @var OutdatedPackage $firstOutdatedPackage */
        $firstOutdatedPackage = reset($outdatedPackages);
        /** @var OutdatedPackage $secondOutdatedPackage */
        $secondOutdatedPackage = next($outdatedPackages);

        static::assertCount(2, $outdatedPackages);

        static::assertSame('composer/composer', $firstOutdatedPackage->getName());
        static::assertSame('1.0.0', $firstOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('1.0.0', $firstOutdatedPackage->getNewVersion());
        static::assertFalse($firstOutdatedPackage->isInsecure());

        static::assertSame('phpunit/phpunit', $secondOutdatedPackage->getName());
        static::assertSame('5.0.10', $secondOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('5.0.10', $secondOutdatedPackage->getNewVersion());
        static::assertTrue($secondOutdatedPackage->isInsecure());
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();
        parent::tearDown();
    }
}
