<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020-2024 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Fixtures;

use Symfony\Component\Filesystem;

use function dirname;

/**
 * ConfigProvider.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ConfigProvider
{
    public static function json(string $filename, bool $returnRelativePath = false): string
    {
        return self::resolveFixturePath($filename.'.json', $returnRelativePath);
    }

    public static function php(string $filename, bool $returnRelativePath = false): string
    {
        return self::resolveFixturePath($filename.'.php', $returnRelativePath);
    }

    public static function yaml(string $filename, bool $returnRelativePath = false): string
    {
        return self::resolveFixturePath($filename.'.yaml', $returnRelativePath);
    }

    public static function yml(string $filename, bool $returnRelativePath = false): string
    {
        return self::resolveFixturePath($filename.'.yml', $returnRelativePath);
    }

    private static function resolveFixturePath(string $filename, bool $returnRelativePath): string
    {
        $rootPath = dirname(__DIR__, 3);
        $fixturePath = __DIR__.'/ConfigFiles/'.$filename;

        if ($returnRelativePath) {
            $fixturePath = Filesystem\Path::makeRelative($fixturePath, $rootPath);
        }

        return $fixturePath;
    }
}
