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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\IO;

use EliasHaeussler\ComposerUpdateCheck\IO\Style;
use EliasHaeussler\ComposerUpdateCheck\Tests\AbstractTestCase;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * StyleTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class StyleTest extends AbstractTestCase
{
    #[Test]
    public function constructorThrowsExceptionIfGivenStyleIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1617549657);

        new Style('foo');
    }

    #[Test]
    public function isSupportedReturnsTrueIfGivenStyleIsSupported(): void
    {
        self::assertTrue(Style::isSupported(Style::NORMAL));
        self::assertTrue(Style::isSupported(Style::JSON));
        self::assertFalse(Style::isSupported('foo'));
    }

    #[Test]
    public function isNormalReturnsTrueIfStyleIsNormal(): void
    {
        $subject = new Style(Style::NORMAL);

        self::assertTrue($subject->isNormal());
        self::assertFalse($subject->isJson());
        self::assertFalse($subject->is('foo'));
    }

    #[Test]
    public function isJsonReturnsTrueIfStyleIsJson(): void
    {
        $subject = new Style(Style::JSON);

        self::assertTrue($subject->isJson());
        self::assertFalse($subject->isNormal());
        self::assertFalse($subject->is('foo'));
    }

    #[Test]
    #[DataProvider('isReturnsTrueIfGivenStyleEqualsStyleDataProvider')]
    public function isReturnsTrueIfGivenStyleEqualsStyle(string $style): void
    {
        $subject = new Style($style);

        self::assertTrue($subject->is($style));
    }

    #[Test]
    public function getStyleReturnsStyle(): void
    {
        $subject = new Style(Style::JSON);

        self::assertSame(Style::JSON, $subject->getStyle());
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function isReturnsTrueIfGivenStyleEqualsStyleDataProvider(): Generator
    {
        yield 'normal style' => [Style::NORMAL];
        yield 'json style' => [Style::JSON];
    }
}
