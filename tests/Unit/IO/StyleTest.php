<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\IO;

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

use EliasHaeussler\ComposerUpdateCheck\IO\Style;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;

/**
 * StyleTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class StyleTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfGivenStyleIsNotSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1617549657);

        new Style('foo');
    }

    /**
     * @test
     */
    public function isSupportedReturnsTrueIfGivenStyleIsSupported(): void
    {
        static::assertTrue(Style::isSupported(Style::NORMAL));
        static::assertTrue(Style::isSupported(Style::JSON));
        static::assertFalse(Style::isSupported('foo'));
    }

    /**
     * @test
     */
    public function isNormalReturnsTrueIfStyleIsNormal(): void
    {
        $subject = new Style(Style::NORMAL);

        static::assertTrue($subject->isNormal());
        static::assertFalse($subject->isJson());
        static::assertFalse($subject->is('foo'));
    }

    /**
     * @test
     */
    public function isJsonReturnsTrueIfStyleIsJson(): void
    {
        $subject = new Style(Style::JSON);

        static::assertTrue($subject->isJson());
        static::assertFalse($subject->isNormal());
        static::assertFalse($subject->is('foo'));
    }

    /**
     * @dataProvider isReturnsTrueIfGivenStyleEqualsStyleDataProvider
     *
     * @test
     */
    public function isReturnsTrueIfGivenStyleEqualsStyle(string $style): void
    {
        $subject = new Style($style);

        static::assertTrue($subject->is($style));
    }

    /**
     * @test
     */
    public function getStyleReturnsStyle(): void
    {
        $subject = new Style(Style::JSON);

        static::assertSame(Style::JSON, $subject->getStyle());
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function isReturnsTrueIfGivenStyleEqualsStyleDataProvider(): \Generator
    {
        yield 'normal style' => [Style::NORMAL];
        yield 'json style' => [Style::JSON];
    }
}
