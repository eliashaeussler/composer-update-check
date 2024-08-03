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

use CuyZ\Valinor;
use EliasHaeussler\ComposerUpdateCheck\Config;
use EliasHaeussler\ComposerUpdateCheck\Exception;
use EliasHaeussler\ComposerUpdateCheck\Helper;

use function getenv;
use function gettype;
use function in_array;
use function preg_replace;
use function sprintf;
use function strtoupper;
use function trim;

final class EnvironmentVariablesConfigAdapter implements ConfigAdapter
{
    private const ENV_VAR_PREFIX = 'COMPOSER_UPDATE_CHECK_';
    private const BOOLEAN_VALUES = [
        'true',
        'yes',
        '1',
    ];

    private readonly Valinor\Mapper\TreeMapper $mapper;

    public function __construct()
    {
        $this->mapper = (new ConfigMapperFactory())->get();
    }

    /**
     * @throws Exception\EnvironmentVariablesAreInvalid
     */
    public function get(): Config\ComposerUpdateCheckConfig
    {
        $config = new Config\ComposerUpdateCheckConfig();
        $configOptions = $config->toArray();

        $resolvedVariables = [];
        $nameMapping = [];

        foreach ($configOptions as $name => $defaultValue) {
            foreach ($this->resolveNames($name) as $propertyPath => $envVarName) {
                $envVarValue = getenv($envVarName);

                // Skip non-existent env variables
                if (false === $envVarValue) {
                    continue;
                }

                Helper\ArrayHelper::setValueByPath(
                    $resolvedVariables,
                    $propertyPath,
                    $this->processValue($envVarValue, $defaultValue),
                );
                Helper\ArrayHelper::setValueByPath($nameMapping, $propertyPath, $envVarName);
            }
        }

        try {
            return $this->mapper->map(
                Config\ComposerUpdateCheckConfig::class,
                Valinor\Mapper\Source\Source::array($resolvedVariables),
            );
        } catch (Valinor\Mapper\MappingError $error) {
            throw new Exception\EnvironmentVariablesAreInvalid($error, $nameMapping);
        }
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @return array<non-empty-string, non-empty-string>
     */
    private function resolveNames(string $propertyName): array
    {
        if ('reporters' === $propertyName) {
            return $this->getReporterEnvVariableNames();
        }

        return [
            $propertyName => self::ENV_VAR_PREFIX.strtoupper((string) preg_replace('/([[:upper:]])/', '_$1', $propertyName)),
        ];
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     */
    private function getReporterEnvVariableNames(): array
    {
        $nameMapping = [];
        $envVarPrefix = self::ENV_VAR_PREFIX.'REPORTER_';
        $envVarPattern = sprintf('/^%s(?P<name>[^_]+)_(?P<option>.+)$/', $envVarPrefix);

        // Resolve reporter config from environment variables
        foreach (getenv() as $name => $value) {
            // Perform cheaper string check before more expensive regex check
            if (!str_starts_with($name, $envVarPattern) || 1 !== preg_match($envVarPattern, $name, $matches)) {
                continue;
            }

            $reporterName = $matches['name'];
            $reporterOption = $matches['option'];

            if ('enable' === $reporterOption) {
                $propertyPath = sprintf('reporters/%s/enabled', $reporterName);
            } else {
                $propertyPath = sprintf('reporters/%s/options/%s', $reporterName, $reporterOption);
            }

            $nameMapping[$propertyPath] = $name;
        }

        return $nameMapping;
    }

    /**
     * @return array<mixed>|bool|string
     */
    private function processValue(string $envVarValue, mixed $defaultValue): array|bool|string
    {
        return match (gettype($defaultValue)) {
            'array' => Helper\ArrayHelper::trimExplode($envVarValue),
            'boolean' => in_array(trim($envVarValue), self::BOOLEAN_VALUES, true),
            default => $envVarValue,
        };
    }
}
