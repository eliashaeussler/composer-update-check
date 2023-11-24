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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\IO;

use EliasHaeussler\ComposerUpdateCheck\IO\Verbosity;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * VerbosityTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class VerbosityTest extends AbstractTestCase
{
    #[Test]
    public function constructorThrowsExceptionIfGivenVerbosityIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1617549839);

        new Verbosity(-1);
    }

    #[Test]
    public function isSupportedReturnsTrueIfVerbosityLevelIsSupported(): void
    {
        self::assertTrue(Verbosity::isSupported(Verbosity::QUIET));
        self::assertTrue(Verbosity::isSupported(Verbosity::NORMAL));
        self::assertTrue(Verbosity::isSupported(Verbosity::VERBOSE));
        self::assertTrue(Verbosity::isSupported(Verbosity::VERY_VERBOSE));
        self::assertTrue(Verbosity::isSupported(Verbosity::DEBUG));
        self::assertFalse(Verbosity::isSupported(-1));
    }

    #[Test]
    public function isQuietReturnsTrueIfVerbosityLevelIsQuiet(): void
    {
        $subject = new Verbosity(Verbosity::QUIET);

        self::assertTrue($subject->isQuiet());
    }

    #[Test]
    public function isNormalReturnsTrueIfVerbosityLevelIsNormal(): void
    {
        $subject = new Verbosity(Verbosity::NORMAL);

        self::assertTrue($subject->isNormal());
    }

    #[Test]
    public function isVerboseReturnsTrueIfVerbosityLevelIsVerbose(): void
    {
        $subject = new Verbosity(Verbosity::VERBOSE);

        self::assertTrue($subject->isVerbose());
    }

    #[Test]
    public function isVeryVerboseReturnsTrueIfVerbosityLevelIsVeryVerbose(): void
    {
        $subject = new Verbosity(Verbosity::VERY_VERBOSE);

        self::assertTrue($subject->isVeryVerbose());
    }

    #[Test]
    public function isDebugReturnsTrueIfVerbosityLevelIsDebug(): void
    {
        $subject = new Verbosity(Verbosity::DEBUG);

        self::assertTrue($subject->isDebug());
    }

    #[Test]
    #[DataProvider('isReturnsTrueIfGivenVerbosityEqualsVerbosityDataProvider')]
    public function isReturnsTrueIfGivenVerbosityEqualsVerbosity(int $verbosity): void
    {
        $subject = new Verbosity($verbosity);

        self::assertTrue($subject->is($verbosity));
    }

    #[Test]
    public function getLevelReturnsVerbosityLevel(): void
    {
        $subject = new Verbosity(Verbosity::VERBOSE);

        self::assertSame(Verbosity::VERBOSE, $subject->getLevel());
    }

    /**
     * @return Generator<string, array{int}>
     */
    public static function isReturnsTrueIfGivenVerbosityEqualsVerbosityDataProvider(): Generator
    {
        yield 'quiet level' => [Verbosity::QUIET];
        yield 'normal level' => [Verbosity::NORMAL];
        yield 'verbose level' => [Verbosity::VERBOSE];
        yield 'very verbose level' => [Verbosity::VERY_VERBOSE];
        yield 'debug level' => [Verbosity::DEBUG];
    }
}
