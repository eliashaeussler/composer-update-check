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
 * UpdateCheckerTest.
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

        $this->composer = $this->getComposer();
        $this->subject = new UpdateChecker($this->composer);
    }

    /**
     * @test
     *
     * @throws JsonValidationException
     */
    public function runReturnsEmptyUpdateCheckResultIfNoPackagesAreRequired(): void
    {
        $this->goToTestDirectory(self::TEST_APPLICATION_EMPTY);

        $subject = new UpdateChecker($this->getComposer());

        $expected = new UpdateCheckResult([]);
        static::assertEquals($expected, $subject->run());
    }

    /**
     * @test
     */
    public function runReturnsEmptyUpdateCheckResultIfOutdatedPackagesAreSkipped(): void
    {
        $expected = new UpdateCheckResult([]);
        static::assertEquals($expected, $this->subject->run(['symfony/*'], false));
    }

    /**
     * @test
     */
    public function runReturnsUpdateCheckResultWithoutDevRequirements(): void
    {
        $outdatedPackages = $this->subject->run([], false)->getOutdatedPackages();
        /** @var OutdatedPackage $firstOutdatedPackage */
        $firstOutdatedPackage = reset($outdatedPackages);
        /** @var OutdatedPackage $secondOutdatedPackage */
        $secondOutdatedPackage = next($outdatedPackages);

        static::assertCount(2, $outdatedPackages);

        static::assertSame('symfony/console', $firstOutdatedPackage->getName());
        static::assertSame('v4.4.9', $firstOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('v4.4.9', $firstOutdatedPackage->getNewVersion());

        static::assertSame('symfony/http-kernel', $secondOutdatedPackage->getName());
        static::assertSame('v4.4.9', $secondOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('v4.4.9', $secondOutdatedPackage->getNewVersion());
    }

    /**
     * @test
     */
    public function runReturnsUpdateCheckResultWithoutSkippedPackages(): void
    {
        $outdatedPackages = $this->subject->run(['symfony/console'])->getOutdatedPackages();
        /** @var OutdatedPackage $firstOutdatedPackage */
        $firstOutdatedPackage = reset($outdatedPackages);
        /** @var OutdatedPackage $secondOutdatedPackage */
        $secondOutdatedPackage = next($outdatedPackages);

        static::assertCount(2, $outdatedPackages);

        static::assertSame('codeception/codeception', $firstOutdatedPackage->getName());
        static::assertSame('4.1.9', $firstOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('4.1.9', $firstOutdatedPackage->getNewVersion());

        static::assertSame('symfony/http-kernel', $secondOutdatedPackage->getName());
        static::assertSame('v4.4.9', $secondOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('v4.4.9', $secondOutdatedPackage->getNewVersion());
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
        /** @var OutdatedPackage $thirdOutdatedPackage */
        $thirdOutdatedPackage = next($outdatedPackages);

        static::assertCount(3, $outdatedPackages);

        static::assertSame('codeception/codeception', $firstOutdatedPackage->getName());
        static::assertSame('4.1.9', $firstOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('4.1.9', $firstOutdatedPackage->getNewVersion());

        static::assertSame('symfony/console', $secondOutdatedPackage->getName());
        static::assertSame('v4.4.9', $secondOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('v4.4.9', $secondOutdatedPackage->getNewVersion());

        static::assertSame('symfony/http-kernel', $thirdOutdatedPackage->getName());
        static::assertSame('v4.4.9', $thirdOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('v4.4.9', $thirdOutdatedPackage->getNewVersion());
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
        /** @var OutdatedPackage $thirdOutdatedPackage */
        $thirdOutdatedPackage = next($outdatedPackages);

        static::assertCount(3, $outdatedPackages);

        static::assertSame('codeception/codeception', $firstOutdatedPackage->getName());
        static::assertSame('4.1.9', $firstOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('4.1.9', $firstOutdatedPackage->getNewVersion());
        static::assertFalse($firstOutdatedPackage->isInsecure());

        static::assertSame('symfony/console', $secondOutdatedPackage->getName());
        static::assertSame('v4.4.9', $secondOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('v4.4.9', $secondOutdatedPackage->getNewVersion());
        static::assertFalse($secondOutdatedPackage->isInsecure());

        static::assertSame('symfony/http-kernel', $thirdOutdatedPackage->getName());
        static::assertSame('v4.4.9', $thirdOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('v4.4.9', $thirdOutdatedPackage->getNewVersion());
        static::assertTrue($thirdOutdatedPackage->isInsecure());
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();
        parent::tearDown();
    }

    /**
     * @throws JsonValidationException
     */
    private function getComposer(): Composer
    {
        $application = new Application();

        return $application->getComposer();
    }
}
