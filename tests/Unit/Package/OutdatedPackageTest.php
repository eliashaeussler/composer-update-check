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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Package;

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use Nyholm\Psr7\Uri;

/**
 * OutdatedPackageTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class OutdatedPackageTest extends AbstractTestCase
{
    /**
     * @var OutdatedPackage
     */
    protected $subjectWithVersion;

    /**
     * @var OutdatedPackage
     */
    protected $subjectWithBranch;

    protected function setUp(): void
    {
        $this->subjectWithVersion = new OutdatedPackage(
            'foo',
            '1.0.0',
            '1.0.5',
            true
        );
        $this->subjectWithBranch = new OutdatedPackage(
            'buu',
            'dev-master 12345',
            'dev-master 67890',
            false
        );
    }

    /**
     * @test
     */
    public function getNameReturnsOutdatedPackageName(): void
    {
        static::assertSame('foo', $this->subjectWithVersion->getName());
        static::assertSame('buu', $this->subjectWithBranch->getName());
    }

    /**
     * @test
     */
    public function setNameSetsNameOfOutdatedPackage(): void
    {
        $this->subjectWithVersion->setName('baz');
        static::assertSame('baz', $this->subjectWithVersion->getName());
        $this->subjectWithBranch->setName('baz');
        static::assertSame('baz', $this->subjectWithBranch->getName());
    }

    /**
     * @test
     */
    public function getOutdatedVersionReturnsOutdatedPackageVersion(): void
    {
        static::assertSame('1.0.0', $this->subjectWithVersion->getOutdatedVersion());
        static::assertSame('dev-master 12345', $this->subjectWithBranch->getOutdatedVersion());
    }

    /**
     * @test
     */
    public function setOutdatedVersionSetsOutdatedPackageVersionOfOutdatedPackage(): void
    {
        $this->subjectWithVersion->setOutdatedVersion('1.0.4');
        static::assertSame('1.0.4', $this->subjectWithVersion->getOutdatedVersion());
        $this->subjectWithBranch->setOutdatedVersion('dev-master 54321');
        static::assertSame('dev-master 54321', $this->subjectWithBranch->getOutdatedVersion());
    }

    /**
     * @test
     */
    public function getNewVersionReturnsNewPackageVersionOfOutdatedPackage(): void
    {
        static::assertSame('1.0.5', $this->subjectWithVersion->getNewVersion());
        static::assertSame('dev-master 67890', $this->subjectWithBranch->getNewVersion());
    }

    /**
     * @test
     */
    public function setNewVersionSetsNewPackageVersionOfOutdatedPackage(): void
    {
        $this->subjectWithVersion->setNewVersion('1.1.0');
        static::assertSame('1.1.0', $this->subjectWithVersion->getNewVersion());
        $this->subjectWithBranch->setNewVersion('dev-master 09876');
        static::assertSame('dev-master 09876', $this->subjectWithBranch->getNewVersion());
    }

    /**
     * @test
     */
    public function isInsecureReturnsSecurityStateOfOutdatedPackage(): void
    {
        static::assertTrue($this->subjectWithVersion->isInsecure());
        static::assertFalse($this->subjectWithBranch->isInsecure());
    }

    /**
     * @test
     */
    public function setInsecureSetsSecurityStateOfOutdatedPackage(): void
    {
        $this->subjectWithVersion->setInsecure(false);
        static::assertFalse($this->subjectWithVersion->isInsecure());
        $this->subjectWithBranch->setInsecure(true);
        static::assertTrue($this->subjectWithBranch->isInsecure());
    }

    /**
     * @test
     */
    public function getProviderLinkReturnsProviderLinkOfOutdatedPackage(): void
    {
        $expected = new Uri('https://packagist.org/packages/foo#1.0.5');
        static::assertEquals($expected, $this->subjectWithVersion->getProviderLink());
        $expected = new Uri('https://packagist.org/packages/buu#dev-master');
        static::assertEquals($expected, $this->subjectWithBranch->getProviderLink());
    }

    /**
     * @test
     */
    public function setProviderLinkSetsProviderLinkOfOutdatedPackage(): void
    {
        $uri = new Uri('https://example.org/foo');
        $this->subjectWithVersion->setProviderLink($uri);
        static::assertSame($uri, $this->subjectWithVersion->getProviderLink());
        $uri = new Uri('https://example.org/buu');
        $this->subjectWithBranch->setProviderLink($uri);
        static::assertSame($uri, $this->subjectWithBranch->getProviderLink());
    }
}
