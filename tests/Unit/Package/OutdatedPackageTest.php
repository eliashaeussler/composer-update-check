<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Package;

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

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;

/**
 * OutdatedPackageTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class OutdatedPackageTest extends AbstractTestCase
{
    /**
     * @var OutdatedPackage
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new OutdatedPackage('foo', '1.0.0', '1.0.5', true);
    }

    /**
     * @test
     */
    public function getNameReturnsOutdatedPackageName(): void
    {
        static::assertSame('foo', $this->subject->getName());
    }

    /**
     * @test
     */
    public function setNameSetsNameOfOutdatedPackage(): void
    {
        $this->subject->setName('baz');
        static::assertSame('baz', $this->subject->getName());
    }

    /**
     * @test
     */
    public function getOutdatedVersionReturnsOutdatedPackageVersion(): void
    {
        static::assertSame('1.0.0', $this->subject->getOutdatedVersion());
    }

    /**
     * @test
     */
    public function setOutdatedVersionSetsOutdatedPackageVersionOfOutdatedPackage(): void
    {
        $this->subject->setOutdatedVersion('1.0.4');
        static::assertSame('1.0.4', $this->subject->getOutdatedVersion());
    }

    /**
     * @test
     */
    public function getNewVersionReturnsNewPackageVersionOfOutdatedPackage(): void
    {
        static::assertSame('1.0.5', $this->subject->getNewVersion());
    }

    /**
     * @test
     */
    public function setNewVersionSetsNewPackageVersionOfOutdatedPackage(): void
    {
        $this->subject->setNewVersion('1.1.0');
        static::assertSame('1.1.0', $this->subject->getNewVersion());
    }

    /**
     * @test
     */
    public function isInsecureReturnsSecurityStateOfOutdatedPackage(): void
    {
        static::assertTrue($this->subject->isInsecure());
    }

    /**
     * @test
     */
    public function setInsecureSetsSecurityStateOfOutdatedPackage(): void
    {
        $this->subject->setInsecure(false);
        static::assertFalse($this->subject->isInsecure());
    }
}
