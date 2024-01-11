<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2024 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Entity\Package;

use DateTimeImmutable;
use EliasHaeussler\ComposerUpdateCheck as Src;
use PHPUnit\Framework;

/**
 * OutdatedPackageTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Entity\Package\OutdatedPackage::class)]
final class OutdatedPackageTest extends Framework\TestCase
{
    private Src\Entity\Package\OutdatedPackage $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Entity\Package\OutdatedPackage(
            'foo/foo',
            new Src\Entity\Version('1.0.0'),
            new Src\Entity\Version('1.0.5'),
            [
                new Src\Entity\Security\SecurityAdvisory(
                    'foo/foo',
                    'advisoryId',
                    '1.0.0-alpha-1',
                    'title',
                    new DateTimeImmutable('2023-12-24 18:00:00'),
                    Src\Entity\Security\SeverityLevel::Critical,
                ),
                new Src\Entity\Security\SecurityAdvisory(
                    'foo/foo',
                    'advisoryId',
                    '>=1.0.0,<1.5.0|2.0.0',
                    'title',
                    new DateTimeImmutable('2023-12-24 18:00:00'),
                    Src\Entity\Security\SeverityLevel::Medium,
                ),
            ],
        );
    }

    #[Framework\Attributes\Test]
    public function getHighestSeverityLevelReturnsNullIfPackageHasNoSecurityAdvisories(): void
    {
        $this->subject->setSecurityAdvisories([]);

        self::assertNull($this->subject->getHighestSeverityLevel());
    }

    #[Framework\Attributes\Test]
    public function getHighestSeverityLevelReturnsHighestSecurityLevel(): void
    {
        self::assertSame(
            Src\Entity\Security\SeverityLevel::Critical,
            $this->subject->getHighestSeverityLevel(),
        );
    }

    #[Framework\Attributes\Test]
    public function isInsecureReturnsTrueIfPackageHasAnySecurityAdvisories(): void
    {
        self::assertTrue($this->subject->isInsecure());

        $this->subject->setSecurityAdvisories([]);

        self::assertFalse($this->subject->isInsecure());
    }
}
