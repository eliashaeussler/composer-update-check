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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Security;

use EliasHaeussler\ComposerUpdateCheck\Entity\Security\SecurityAdvisory;
use EliasHaeussler\ComposerUpdateCheck\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * InsecurePackageTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class InsecurePackageTest extends AbstractTestCase
{
    private SecurityAdvisory $subject;

    protected function setUp(): void
    {
        $this->subject = new SecurityAdvisory('foo', ['>=1.0.0,<1.0.5', '>=2.5.0,<2.6.0']);
    }

    #[Test]
    public function getNameReturnsInsecurePackageName(): void
    {
        self::assertSame('foo', $this->subject->getPackageName());
    }

    #[Test]
    public function getNameSetsNameOfInsecurePackage(): void
    {
        $this->subject->setName('baz');
        self::assertSame('baz', $this->subject->getPackageName());
    }

    #[Test]
    public function getAffectedVersionsReturnsAffectedVersionsOfInsecurePackage(): void
    {
        self::assertSame(['>=1.0.0,<1.0.5', '>=2.5.0,<2.6.0'], $this->subject->getAffectedVersions());
    }

    #[Test]
    public function setAffectedVersionsSetsAffectedVersionsOfInsecurePackage(): void
    {
        $this->subject->setAffectedVersions(['3.0.0']);
        self::assertSame(['3.0.0'], $this->subject->getAffectedVersions());
    }
}
