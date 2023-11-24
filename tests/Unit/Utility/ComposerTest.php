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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Utility;

use Composer\Plugin\PluginInterface;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Utility\Composer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

/**
 * ComposerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ComposerTest extends AbstractTestCase
{
    #[Test]
    public function getPlatformVersionReturnsFullPlatformVersion(): void
    {
        $expected = PluginInterface::PLUGIN_API_VERSION;
        self::assertSame($expected, Composer::getPlatformVersion(Composer::VERSION_FULL));
    }

    #[Test]
    public function getPlatformVersionReturnsMajorPlatformVersion(): void
    {
        [$expected] = explode('.', PluginInterface::PLUGIN_API_VERSION);
        self::assertSame($expected, Composer::getPlatformVersion(Composer::VERSION_MAJOR));
    }

    #[Test]
    public function getPlatformVersionReturnsPlatformBranch(): void
    {
        [$major, $minor] = explode('.', PluginInterface::PLUGIN_API_VERSION);
        $expected = $major.'.'.$minor;
        self::assertSame($expected, Composer::getPlatformVersion(Composer::VERSION_BRANCH));
    }

    #[Test]
    public function getPlatformVersionThrowsExceptionIfUnsupportedVersionTypeIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1603794822);
        Composer::getPlatformVersion(-99);
    }

    #[Test]
    public function getMajorVersionReturnsMajorPlatformVersion(): void
    {
        [$expected] = explode('.', PluginInterface::PLUGIN_API_VERSION);
        self::assertSame((int) $expected, Composer::getMajorVersion());
    }
}
