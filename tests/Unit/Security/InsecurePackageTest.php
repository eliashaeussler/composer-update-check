<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Security;

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

use EliasHaeussler\ComposerUpdateCheck\Security\InsecurePackage;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;

/**
 * InsecurePackageTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class InsecurePackageTest extends AbstractTestCase
{
    /**
     * @var InsecurePackage
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new InsecurePackage('foo', ['>=1.0.0,<1.0.5', '>=2.5.0,<2.6.0']);
    }

    /**
     * @test
     */
    public function getNameReturnsInsecurePackageName(): void
    {
        static::assertSame('foo', $this->subject->getName());
    }

    /**
     * @test
     */
    public function getNameSetsNameOfInsecurePackage(): void
    {
        $this->subject->setName('baz');
        static::assertSame('baz', $this->subject->getName());
    }

    /**
     * @test
     */
    public function getAffectedVersionsReturnsAffectedVersionsOfInsecurePackage(): void
    {
        static::assertSame(['>=1.0.0,<1.0.5', '>=2.5.0,<2.6.0'], $this->subject->getAffectedVersions());
    }

    /**
     * @test
     */
    public function setAffectedVersionsSetsAffectedVersionsOfInsecurePackage(): void
    {
        $this->subject->setAffectedVersions(['3.0.0']);
        static::assertSame(['3.0.0'], $this->subject->getAffectedVersions());
    }
}
