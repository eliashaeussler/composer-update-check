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
final class OutdatedPackageTest extends AbstractTestCase
{
    private OutdatedPackage $subjectWithVersion;
    private OutdatedPackage $subjectWithBranch;

    protected function setUp(): void
    {
        $this->subjectWithVersion = new OutdatedPackage(
            'foo',
            '1.0.0',
            '1.0.5',
            true,
        );
        $this->subjectWithBranch = new OutdatedPackage(
            'buu',
            'dev-master 12345',
            'dev-master 67890',
            false,
        );
    }

    /**
     * @test
     */
    public function getNameReturnsOutdatedPackageName(): void
    {
        self::assertSame('foo', $this->subjectWithVersion->getName());
        self::assertSame('buu', $this->subjectWithBranch->getName());
    }

    /**
     * @test
     */
    public function getOutdatedVersionReturnsOutdatedPackageVersion(): void
    {
        self::assertSame('1.0.0', $this->subjectWithVersion->getOutdatedVersion());
        self::assertSame('dev-master 12345', $this->subjectWithBranch->getOutdatedVersion());
    }

    /**
     * @test
     */
    public function getNewVersionReturnsNewPackageVersionOfOutdatedPackage(): void
    {
        self::assertSame('1.0.5', $this->subjectWithVersion->getNewVersion());
        self::assertSame('dev-master 67890', $this->subjectWithBranch->getNewVersion());
    }

    /**
     * @test
     */
    public function isInsecureReturnsSecurityStateOfOutdatedPackage(): void
    {
        self::assertTrue($this->subjectWithVersion->isInsecure());
        self::assertFalse($this->subjectWithBranch->isInsecure());
    }

    /**
     * @test
     */
    public function setInsecureSetsSecurityStateOfOutdatedPackage(): void
    {
        $this->subjectWithVersion->setInsecure(false);
        self::assertFalse($this->subjectWithVersion->isInsecure());
        $this->subjectWithBranch->setInsecure(true);
        self::assertTrue($this->subjectWithBranch->isInsecure());
    }

    /**
     * @test
     */
    public function getProviderLinkReturnsProviderLinkOfOutdatedPackage(): void
    {
        $expected = new Uri('https://packagist.org/packages/foo#1.0.5');
        self::assertEquals($expected, $this->subjectWithVersion->getProviderLink());
        $expected = new Uri('https://packagist.org/packages/buu#dev-master');
        self::assertEquals($expected, $this->subjectWithBranch->getProviderLink());
    }
}
