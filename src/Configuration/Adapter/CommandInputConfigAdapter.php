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

use EliasHaeussler\ComposerUpdateCheck\Configuration\ComposerUpdateCheckConfig;
use EliasHaeussler\ComposerUpdateCheck\Configuration\Options\PackageExcludePattern;
use Symfony\Component\Console\Input\InputInterface;

use function is_string;
use function str_starts_with;

/**
 * CommandInputConfigAdapter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class CommandInputConfigAdapter implements ConfigAdapter
{
    public function __construct(
        private readonly InputInterface $input,
    ) {}

    public function resolve(): ComposerUpdateCheckConfig
    {
        $config = new ComposerUpdateCheckConfig();

        if ($this->input->hasOption('ignore-packages')) {
            /** @var array<string> $excludePatterns */
            $excludePatterns = $this->input->getOption('ignore-packages');

            foreach ($excludePatterns as $pattern) {
                $excludePattern = $this->resolveExcludePattern($pattern);
                $config->excludePackageByPattern($excludePattern);
            }
        }

        if ($this->input->hasOption('no-dev') && true === $this->input->getOption('no-dev')) {
            $config->excludeDevPackages();
        }

        if ($this->input->hasOption('security-scan') && true === $this->input->getOption('security-scan')) {
            $config->performSecurityScan();
        }

        if ($this->input->hasOption('format') && is_string($this->input->getOption('format'))) {
            $config->setFormat($this->input->getOption('format'));
        }

        return $config;
    }

    private function resolveExcludePattern(string $pattern): PackageExcludePattern
    {
        if (str_starts_with($pattern, '/') || str_starts_with($pattern, '#')) {
            return PackageExcludePattern::byRegularExpression($pattern);
        }

        return PackageExcludePattern::byName($pattern);
    }
}
