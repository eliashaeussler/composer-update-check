<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020-2026 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Entity;

use EliasHaeussler\ComposerUpdateCheck as Src;
use PHPUnit\Framework;

/**
 * VersionTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Entity\Version::class)]
final class VersionTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function sha1ReturnsNullIfHashIsNotDefined(): void
    {
        $subject = new Src\Entity\Version('1.0.0');

        self::assertNull($subject->sha1());
    }

    #[Framework\Attributes\Test]
    public function sha1CanReturnShortHash(): void
    {
        $subject = new Src\Entity\Version('dev-latest', '58efaa4d8f20cd5fcaf511da110c6ad31a1263e1');

        self::assertSame('58efaa4', $subject->sha1(true));
    }

    #[Framework\Attributes\Test]
    public function sha1CanReturnLongHash(): void
    {
        $subject = new Src\Entity\Version('dev-latest', '58efaa4d8f20cd5fcaf511da110c6ad31a1263e1');

        self::assertSame('58efaa4d8f20cd5fcaf511da110c6ad31a1263e1', $subject->sha1());
    }

    #[Framework\Attributes\Test]
    public function toStringIncludesShortHash(): void
    {
        $subject = new Src\Entity\Version('dev-latest', '58efaa4d8f20cd5fcaf511da110c6ad31a1263e1');

        self::assertSame('dev-latest (58efaa4)', $subject->toString());
    }
}
