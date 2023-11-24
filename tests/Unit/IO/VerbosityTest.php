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

use EliasHaeussler\ComposerUpdateCheck\IO\Verbosity;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;

/**
 * VerbosityTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class VerbosityTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfGivenVerbosityIsNotSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1617549839);

        new Verbosity(-1);
    }

    /**
     * @test
     */
    public function isSupportedReturnsTrueIfVerbosityLevelIsSupported(): void
    {
        static::assertTrue(Verbosity::isSupported(Verbosity::QUIET));
        static::assertTrue(Verbosity::isSupported(Verbosity::NORMAL));
        static::assertTrue(Verbosity::isSupported(Verbosity::VERBOSE));
        static::assertTrue(Verbosity::isSupported(Verbosity::VERY_VERBOSE));
        static::assertTrue(Verbosity::isSupported(Verbosity::DEBUG));
        static::assertFalse(Verbosity::isSupported(-1));
    }

    /**
     * @test
     */
    public function isQuietReturnsTrueIfVerbosityLevelIsQuiet(): void
    {
        $subject = new Verbosity(Verbosity::QUIET);

        static::assertTrue($subject->isQuiet());
    }

    /**
     * @test
     */
    public function isNormalReturnsTrueIfVerbosityLevelIsNormal(): void
    {
        $subject = new Verbosity(Verbosity::NORMAL);

        static::assertTrue($subject->isNormal());
    }

    /**
     * @test
     */
    public function isVerboseReturnsTrueIfVerbosityLevelIsVerbose(): void
    {
        $subject = new Verbosity(Verbosity::VERBOSE);

        static::assertTrue($subject->isVerbose());
    }

    /**
     * @test
     */
    public function isVeryVerboseReturnsTrueIfVerbosityLevelIsVeryVerbose(): void
    {
        $subject = new Verbosity(Verbosity::VERY_VERBOSE);

        static::assertTrue($subject->isVeryVerbose());
    }

    /**
     * @test
     */
    public function isDebugReturnsTrueIfVerbosityLevelIsDebug(): void
    {
        $subject = new Verbosity(Verbosity::DEBUG);

        static::assertTrue($subject->isDebug());
    }

    /**
     * @dataProvider isReturnsTrueIfGivenVerbosityEqualsVerbosityDataProvider
     *
     * @test
     */
    public function isReturnsTrueIfGivenVerbosityEqualsVerbosity(int $verbosity): void
    {
        $subject = new Verbosity($verbosity);

        static::assertTrue($subject->is($verbosity));
    }

    /**
     * @test
     */
    public function getLevelReturnsVerbosityLevel(): void
    {
        $subject = new Verbosity(Verbosity::VERBOSE);

        static::assertSame(Verbosity::VERBOSE, $subject->getLevel());
    }

    /**
     * @return \Generator<string, array{int}>
     */
    public static function isReturnsTrueIfGivenVerbosityEqualsVerbosityDataProvider(): \Generator
    {
        yield 'quiet level' => [Verbosity::QUIET];
        yield 'normal level' => [Verbosity::NORMAL];
        yield 'verbose level' => [Verbosity::VERBOSE];
        yield 'very verbose level' => [Verbosity::VERY_VERBOSE];
        yield 'debug level' => [Verbosity::DEBUG];
    }
}
