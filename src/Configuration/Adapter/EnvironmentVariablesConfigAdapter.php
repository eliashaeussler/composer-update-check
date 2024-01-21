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

namespace EliasHaeussler\ComposerUpdateCheck\Configuration\Adapter;

use EliasHaeussler\ComposerUpdateCheck\Configuration;
use Generator;

use function array_filter;
use function explode;
use function getenv;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * EnvironmentVariablesConfigAdapter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class EnvironmentVariablesConfigAdapter implements ConfigAdapter
{
    private const ENV_VAR_PREFIX = 'COMPOSER_UPDATE_CHECK_';

    public function resolve(): Configuration\ComposerUpdateCheckConfig
    {
        $config = new Configuration\ComposerUpdateCheckConfig();

        // Exclude patterns
        $excludePatterns = $this->getEnv('EXCLUDE_PATTERNS', true);
        foreach ($excludePatterns as $excludePattern) {
            $config->excludePackageByPattern(Configuration\Options\PackageExcludePattern::create($excludePattern));
        }

        // Include dev packages
        $noDev = $this->getEnv('NO_DEV');
        if ($this->isTrue($noDev)) {
            $config->excludeDevPackages();
        }

        // Perform security scan
        $securityScan = $this->getEnv('SECURITY_SCAN');
        if ($this->isTrue($securityScan)) {
            $config->performSecurityScan();
        }

        // Format
        $format = $this->getEnv('FORMAT');
        if (null !== $format) {
            $config->setFormat($format);
        }

        // Reporters
        foreach ($this->getReporterEnvVariables() as $name => $reporterConfig) {
            if ($reporterConfig['enable']) {
                $config->enableReporter($name, $reporterConfig['options'] ?? []);
            } else {
                $config->disableReporter($name);
            }
        }

        return $config;
    }

    /**
     * @phpstan-return ($list is true ? array<string> : string|null)
     */
    private function getEnv(string $name, bool $list = false): string|array|null
    {
        $value = getenv(self::ENV_VAR_PREFIX.$name);

        if (false === $value || '' === trim($value)) {
            return $list ? [] : null;
        }

        if (!$list) {
            return $value;
        }

        return explode(',', $value);
    }

    /**
     * @return Generator<string, array{enable: bool, options?: array<string, mixed>}>
     */
    private function getReporterEnvVariables(): Generator
    {
        $reporterConfig = [];
        $envVarPrefix = self::ENV_VAR_PREFIX.'REPORTER_';
        $envVarPattern = sprintf('/^%s(?P<name>[^_]+)_(?P<option>.+)$/', $envVarPrefix);

        // Fetch all relevant environment variables
        $envVariables = array_filter(
            getenv(),
            static fn (string $name) => str_starts_with($name, $envVarPrefix),
            ARRAY_FILTER_USE_KEY,
        );

        // Resolve reporter config from environment variables
        foreach ($envVariables as $name => $value) {
            if (1 !== preg_match($envVarPattern, $name, $matches)) {
                continue;
            }

            $reporterName = strtolower($matches['name']);
            $reporterOption = strtolower($matches['option']);

            $reporterConfig[$reporterName][$reporterOption] ??= $value;
        }

        // Yield reporter config
        foreach ($reporterConfig as $name => $config) {
            $enabled = $this->isTrue($config['enable'] ?? '1');

            unset($config['enable']);

            if ($enabled) {
                yield $name => [
                    'enable' => true,
                    'options' => $config,
                ];
            } else {
                yield $name => [
                    'enable' => false,
                ];
            }
        }
    }

    private function isTrue(string|null $value): bool
    {
        if (null === $value) {
            return false;
        }

        $value = trim($value);

        if ('true' === $value || 'yes' === $value) {
            return true;
        }

        if ('false' === $value || 'no' === $value) {
            return false;
        }

        return (bool) $value;
    }
}
