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

namespace EliasHaeussler\ComposerUpdateCheck\Configuration\Adapter;

use EliasHaeussler\ComposerUpdateCheck\Configuration;
use EliasHaeussler\ComposerUpdateCheck\IO;

/**
 * ChainedConfigAdapter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ChainedConfigAdapter implements ConfigAdapter
{
    /**
     * @param list<ConfigAdapter> $adapters
     */
    public function __construct(
        private readonly array $adapters,
    ) {}

    public function resolve(): Configuration\ComposerUpdateCheckConfig
    {
        $config = new Configuration\ComposerUpdateCheckConfig();

        foreach ($this->adapters as $adapter) {
            $this->mergeConfigs($config, $adapter->resolve());
        }

        return $config;
    }

    private function mergeConfigs(
        Configuration\ComposerUpdateCheckConfig $config,
        Configuration\ComposerUpdateCheckConfig $other,
    ): void {
        foreach ($other->getExcludePatterns() as $excludePattern) {
            $config->excludePackageByPattern($excludePattern);
        }

        if (!$other->areDevPackagesIncluded()) {
            $config->excludeDevPackages();
        }

        if ($other->shouldPerformSecurityScan()) {
            $config->performSecurityScan();
        }

        if (IO\Formatter\TextFormatter::FORMAT !== $other->getFormat()) {
            $config->setFormat($other->getFormat());
        }

        foreach ($other->getReporters() as $name => $options) {
            $config->enableReporter($name, $options);
        }
    }
}
