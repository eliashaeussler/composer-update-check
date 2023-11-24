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

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020 Elias Häußler <elias@haeussler.dev>
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

use Composer\Plugin\PluginInterface;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Utility\Composer;
use InvalidArgumentException;

/**
 * ComposerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class ComposerTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function getPlatformVersionReturnsFullPlatformVersion(): void
    {
        $expected = PluginInterface::PLUGIN_API_VERSION;
        static::assertSame($expected, Composer::getPlatformVersion(Composer::VERSION_FULL));
    }

    /**
     * @test
     */
    public function getPlatformVersionReturnsMajorPlatformVersion(): void
    {
        [$expected] = explode('.', PluginInterface::PLUGIN_API_VERSION);
        static::assertSame($expected, Composer::getPlatformVersion(Composer::VERSION_MAJOR));
    }

    /**
     * @test
     */
    public function getPlatformVersionReturnsPlatformBranch(): void
    {
        [$major, $minor] = explode('.', PluginInterface::PLUGIN_API_VERSION);
        $expected = $major.'.'.$minor;
        static::assertSame($expected, Composer::getPlatformVersion(Composer::VERSION_BRANCH));
    }

    /**
     * @test
     */
    public function getPlatformVersionThrowsExceptionIfUnsupportedVersionTypeIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1603794822);
        Composer::getPlatformVersion(-99);
    }

    /**
     * @test
     */
    public function getMajorVersionReturnsMajorPlatformVersion(): void
    {
        [$expected] = explode('.', PluginInterface::PLUGIN_API_VERSION);
        static::assertSame((int) $expected, Composer::getMajorVersion());
    }
}
