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

namespace EliasHaeussler\ComposerUpdateCheck\Config\Adapter;

use EliasHaeussler\ComposerUpdateCheck\Config;
use EliasHaeussler\ComposerUpdateCheck\Exception;
use Symfony\Component\Filesystem;

use function file_exists;
use function is_callable;

/**
 * PhpConfigAdapter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class PhpConfigAdapter implements ConfigAdapter
{
    public function __construct(
        private readonly string $file,
    ) {}

    /**
     * @throws Exception\ConfigFileIsMissing
     * @throws Exception\ConfigFileIsNotSupported
     */
    public function get(): Config\ComposerUpdateCheckConfig
    {
        if (!file_exists($this->file)) {
            throw new Exception\ConfigFileIsMissing($this->file);
        }

        if ('php' !== Filesystem\Path::getExtension($this->file, true)) {
            throw new Exception\ConfigFileIsNotSupported($this->file);
        }

        $closure = require $this->file;

        if (!is_callable($closure)) {
            throw new Exception\ConfigFileIsNotSupported($this->file);
        }

        $config = new Config\ComposerUpdateCheckConfig();

        // We use static method to decouple closure from adapter object context
        return self::fetchConfig($closure, $config);
    }

    private static function fetchConfig(
        callable $closure,
        Config\ComposerUpdateCheckConfig $config,
    ): Config\ComposerUpdateCheckConfig {
        $resolvedConfig = $closure($config);

        if (!($resolvedConfig instanceof Config\ComposerUpdateCheckConfig)) {
            $resolvedConfig = $config;
        }

        return $resolvedConfig;
    }
}
