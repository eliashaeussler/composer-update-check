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
use Composer\IO\NullIO;
use Composer\Json\JsonValidationException;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;
use EliasHaeussler\ComposerUpdateCheck\IO\OutputBehavior;
use EliasHaeussler\ComposerUpdateCheck\IO\Style;
use EliasHaeussler\ComposerUpdateCheck\IO\Verbosity;
use EliasHaeussler\ComposerUpdateCheck\Options;
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
     * @var OutputBehavior
     */
    protected $behavior;

    /**
     * @var Options
     */
    protected $options;

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
        $this->behavior = new OutputBehavior(new Style(), new Verbosity(), new NullIO());
        $this->options = new Options();
        $this->subject = new UpdateChecker($this->composer, $this->behavior, $this->options);
    }

    /**
     * @test
     *
     * @throws JsonValidationException
     */
    public function runReturnsEmptyUpdateCheckResultIfNoPackagesAreRequired(): void
    {
        $this->goToTestDirectory(self::TEST_APPLICATION_EMPTY);

        $subject = new UpdateChecker($this->getComposer(), $this->behavior, $this->options);

        $expected = new UpdateCheckResult([]);
        static::assertEquals($expected, $subject->run());
    }

    /**
     * @test
     */
    public function runReturnsEmptyUpdateCheckResultIfOutdatedPackagesAreSkipped(): void
    {
        $this->options->setIgnorePackages(['symfony/*']);
        $this->options->setIncludeDevPackages(false);

        $expected = new UpdateCheckResult([]);
        static::assertEquals($expected, $this->subject->run());
    }

    /**
     * @test
     */
    public function runReturnsUpdateCheckResultWithoutDevRequirements(): void
    {
        $this->options->setIncludeDevPackages(false);

        $outdatedPackages = $this->subject->run()->getOutdatedPackages();
        $firstOutdatedPackage = reset($outdatedPackages);
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
        $this->options->setIgnorePackages(['symfony/console']);

        $outdatedPackages = $this->subject->run()->getOutdatedPackages();
        $firstOutdatedPackage = reset($outdatedPackages);
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
        $firstOutdatedPackage = reset($outdatedPackages);
        $secondOutdatedPackage = next($outdatedPackages);
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
        $this->options->setPerformSecurityScan(true);

        $outdatedPackages = $this->subject->run()->getOutdatedPackages();
        $firstOutdatedPackage = reset($outdatedPackages);
        $secondOutdatedPackage = next($outdatedPackages);
        $thirdOutdatedPackage = next($outdatedPackages);

        static::assertCount(3, $outdatedPackages);

        static::assertSame('codeception/codeception', $firstOutdatedPackage->getName());
        static::assertSame('4.1.9', $firstOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('4.1.9', $firstOutdatedPackage->getNewVersion());
        static::assertTrue($firstOutdatedPackage->isInsecure());

        static::assertSame('symfony/console', $secondOutdatedPackage->getName());
        static::assertSame('v4.4.9', $secondOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('v4.4.9', $secondOutdatedPackage->getNewVersion());
        static::assertFalse($secondOutdatedPackage->isInsecure());

        static::assertSame('symfony/http-kernel', $thirdOutdatedPackage->getName());
        static::assertSame('v4.4.9', $thirdOutdatedPackage->getOutdatedVersion());
        static::assertNotSame('v4.4.9', $thirdOutdatedPackage->getNewVersion());
        static::assertTrue($thirdOutdatedPackage->isInsecure());
    }

    /**
     * @test
     */
    public function runDispatchesPostUpdateCheckEvent(): void
    {
        $listener = function (PostUpdateCheckEvent $event) {
            $outdatedPackages = $event->getUpdateCheckResult()->getOutdatedPackages();

            static::assertSame($this->behavior, $event->getBehavior());
            static::assertSame($this->options, $event->getOptions());

            $outdatedPackage = reset($outdatedPackages);

            static::assertCount(1, $outdatedPackages);
            static::assertSame('symfony/http-kernel', $outdatedPackage->getName());
            static::assertSame('v4.4.9', $outdatedPackage->getOutdatedVersion());
            static::assertNotSame('v4.4.9', $outdatedPackage->getNewVersion());
        };

        $this->options->setIgnorePackages(['symfony/console']);
        $this->options->setIncludeDevPackages(false);
        $this->options->setPerformSecurityScan(true);
        $this->composer->getEventDispatcher()->addListener(PostUpdateCheckEvent::NAME, $listener);

        $this->subject->run();
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
