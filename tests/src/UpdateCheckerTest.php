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

namespace EliasHaeussler\ComposerUpdateCheck\Tests;

use Composer\Composer;
use Composer\Console\Application;
use Composer\IO\NullIO;
use Composer\Json\JsonValidationException;
use EliasHaeussler\ComposerUpdateCheck\Entity\Result\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;
use EliasHaeussler\ComposerUpdateCheck\IO\OutputBehavior;
use EliasHaeussler\ComposerUpdateCheck\IO\Style;
use EliasHaeussler\ComposerUpdateCheck\IO\Verbosity;
use EliasHaeussler\ComposerUpdateCheck\Options;
use EliasHaeussler\ComposerUpdateCheck\UpdateChecker;
use PHPUnit\Framework\Attributes\Test;

/**
 * UpdateCheckerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateCheckerTest extends AbstractTestCase
{
    use TestApplicationTrait;

    private Composer $composer;
    private OutputBehavior $behavior;
    private Options $options;
    private UpdateChecker $subject;

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
     * @throws JsonValidationException
     */
    #[Test]
    public function runReturnsEmptyUpdateCheckResultIfNoPackagesAreRequired(): void
    {
        $this->goToTestDirectory(self::TEST_APPLICATION_EMPTY);

        $subject = new UpdateChecker($this->getComposer(), $this->behavior, $this->options);

        $expected = new UpdateCheckResult([]);
        self::assertEquals($expected, $subject->run());
    }

    #[Test]
    public function runReturnsEmptyUpdateCheckResultIfOutdatedPackagesAreSkipped(): void
    {
        $this->options->setIgnorePackages(['symfony/*']);
        $this->options->setIncludeDevPackages(false);

        $expected = new UpdateCheckResult([]);
        self::assertEquals($expected, $this->subject->run());
    }

    #[Test]
    public function runReturnsUpdateCheckResultWithoutDevRequirements(): void
    {
        $this->options->setIncludeDevPackages(false);

        $outdatedPackages = $this->subject->run()->getOutdatedPackages();
        $firstOutdatedPackage = reset($outdatedPackages);
        $secondOutdatedPackage = next($outdatedPackages);

        self::assertCount(2, $outdatedPackages);

        self::assertSame('symfony/console', $firstOutdatedPackage->getName());
        self::assertSame('v4.4.9', $firstOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('v4.4.9', $firstOutdatedPackage->getNewVersion());

        self::assertSame('symfony/http-kernel', $secondOutdatedPackage->getName());
        self::assertSame('v4.4.9', $secondOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('v4.4.9', $secondOutdatedPackage->getNewVersion());
    }

    #[Test]
    public function runReturnsUpdateCheckResultWithoutSkippedPackages(): void
    {
        $this->options->setIgnorePackages(['symfony/console']);

        $outdatedPackages = $this->subject->run()->getOutdatedPackages();
        $firstOutdatedPackage = reset($outdatedPackages);
        $secondOutdatedPackage = next($outdatedPackages);

        self::assertCount(2, $outdatedPackages);

        self::assertSame('codeception/codeception', $firstOutdatedPackage->getName());
        self::assertSame('4.1.9', $firstOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('4.1.9', $firstOutdatedPackage->getNewVersion());

        self::assertSame('symfony/http-kernel', $secondOutdatedPackage->getName());
        self::assertSame('v4.4.9', $secondOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('v4.4.9', $secondOutdatedPackage->getNewVersion());
    }

    #[Test]
    public function runReturnsUpdateCheckResultListOfOutdatedPackages(): void
    {
        $outdatedPackages = $this->subject->run()->getOutdatedPackages();
        $firstOutdatedPackage = reset($outdatedPackages);
        $secondOutdatedPackage = next($outdatedPackages);
        $thirdOutdatedPackage = next($outdatedPackages);

        self::assertCount(3, $outdatedPackages);

        self::assertSame('codeception/codeception', $firstOutdatedPackage->getName());
        self::assertSame('4.1.9', $firstOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('4.1.9', $firstOutdatedPackage->getNewVersion());

        self::assertSame('symfony/console', $secondOutdatedPackage->getName());
        self::assertSame('v4.4.9', $secondOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('v4.4.9', $secondOutdatedPackage->getNewVersion());

        self::assertSame('symfony/http-kernel', $thirdOutdatedPackage->getName());
        self::assertSame('v4.4.9', $thirdOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('v4.4.9', $thirdOutdatedPackage->getNewVersion());
    }

    #[Test]
    public function runReturnsUpdateCheckResultListOfOutdatedPackagesAndFlagsInsecurePackages(): void
    {
        $this->options->setPerformSecurityScan(true);

        $outdatedPackages = $this->subject->run()->getOutdatedPackages();
        $firstOutdatedPackage = reset($outdatedPackages);
        $secondOutdatedPackage = next($outdatedPackages);
        $thirdOutdatedPackage = next($outdatedPackages);

        self::assertCount(3, $outdatedPackages);

        self::assertSame('codeception/codeception', $firstOutdatedPackage->getName());
        self::assertSame('4.1.9', $firstOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('4.1.9', $firstOutdatedPackage->getNewVersion());
        self::assertTrue($firstOutdatedPackage->isInsecure());

        self::assertSame('symfony/console', $secondOutdatedPackage->getName());
        self::assertSame('v4.4.9', $secondOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('v4.4.9', $secondOutdatedPackage->getNewVersion());
        self::assertFalse($secondOutdatedPackage->isInsecure());

        self::assertSame('symfony/http-kernel', $thirdOutdatedPackage->getName());
        self::assertSame('v4.4.9', $thirdOutdatedPackage->getOutdatedVersion());
        self::assertNotSame('v4.4.9', $thirdOutdatedPackage->getNewVersion());
        self::assertTrue($thirdOutdatedPackage->isInsecure());
    }

    #[Test]
    public function runDispatchesPostUpdateCheckEvent(): void
    {
        $listener = function (PostUpdateCheckEvent $event) {
            $outdatedPackages = $event->getUpdateCheckResult()->getOutdatedPackages();

            self::assertSame($this->behavior, $event->getBehavior());
            self::assertSame($this->options, $event->getOptions());

            $outdatedPackage = reset($outdatedPackages);

            self::assertCount(1, $outdatedPackages);
            self::assertSame('symfony/http-kernel', $outdatedPackage->getName());
            self::assertSame('v4.4.9', $outdatedPackage->getOutdatedVersion());
            self::assertNotSame('v4.4.9', $outdatedPackage->getNewVersion());
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
