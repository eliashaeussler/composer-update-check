<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Utility;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2021 Elias Häußler <elias@haeussler.dev>
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

use Composer\Console\Application;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Package\PackageInterface;
use Composer\Semver\Constraint\Constraint;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\TestApplicationTrait;
use EliasHaeussler\ComposerUpdateCheck\Utility\Installer;
use EliasHaeussler\ComposerUpdateCheck\Utility\PackageManager;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * PackageManagerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class PackageManagerTest extends AbstractTestCase
{
    use TestApplicationTrait;

    /**
     * @var PackageManager
     */
    protected $subject;

    /**
     * @var BufferIO
     */
    protected $io;

    protected function setUp(): void
    {
        $this->goToTestDirectory();

        $composer = (new Application())->getComposer();
        $formatter = new OutputFormatter(false, Factory::createAdditionalStyles());
        $this->io = new BufferIO('', StreamOutput::VERBOSITY_NORMAL, $formatter);
        $this->subject = new PackageManager($composer, $this->io);
        Installer::runInstall($composer);
    }

    /**
     * @test
     */
    public function isPackageInstalledReturnsPackageInstallState(): void
    {
        self::assertTrue($this->subject->isPackageInstalled('phpunit/phpunit'));
        self::assertFalse($this->subject->isPackageInstalled('foo/baz'));
    }

    /**
     * @test
     */
    public function isPackageInstalledRespectsConstraint(): void
    {
        $constraint = new Constraint('<', '6.0.0');
        self::assertTrue($this->subject->isPackageInstalled('phpunit/phpunit', $constraint));
        $constraint = new Constraint('>=', '6.0.0');
        self::assertFalse($this->subject->isPackageInstalled('phpunit/phpunit', $constraint));
    }

    /**
     * @test
     */
    public function suggestRequirementPrintsInstallSuggestion(): void
    {
        $this->subject->suggestRequirement('phpunit/phpunit', '^9.4');
        self::assertSame(
            'Package phpunit/phpunit (installed as 5.0.10) might be an incompatible requirement. Suggested requirement: ^9.4',
            trim($this->io->getOutput())
        );
    }

    /**
     * @test
     */
    public function suggestRequirementDoesNotOutputAnythingIfPackageIsNotInstalled(): void
    {
        $this->subject->suggestRequirement('foo/baz', '1.0.0');
        self::assertSame('', $this->io->getOutput());
    }

    /**
     * @test
     */
    public function getPackageReturnsPackageObjectIfPackageIsInstalled(): void
    {
        $package = $this->subject->getPackage('phpunit/phpunit');
        self::assertInstanceOf(PackageInterface::class, $package);
        self::assertSame('phpunit/phpunit', $package->getName());
    }

    /**
     * @test
     */
    public function getPackageReturnsNullIfPackageIsNotInstalled(): void
    {
        self::assertNull($this->subject->getPackage('foo/baz'));
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();
        parent::tearDown();
    }
}
