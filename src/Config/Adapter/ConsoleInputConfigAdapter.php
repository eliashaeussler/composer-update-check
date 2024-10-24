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
use JsonException;
use Symfony\Component\Console;

use function array_filter;
use function is_array;
use function is_string;
use function str_starts_with;
use function substr;
use function trim;

/**
 * ConsoleInputConfigAdapter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ConsoleInputConfigAdapter implements ConfigAdapter
{
    private const PARAMETER_MAPPING = [
        'excludePatterns' => '--exclude',
        'noDev' => '--no-dev',
        'securityScan' => '--security-scan',
        'format' => '--format',
    ];

    private readonly Valinor\Mapper\TreeMapper $mapper;

    public function __construct(
        private readonly Console\Input\InputInterface $input,
    ) {
        $this->mapper = (new ConfigMapperFactory())->get();
    }

    /**
     * @throws Exception\CommandParametersAreInvalid
     * @throws Exception\ReporterOptionsAreInvalid
     */
    public function get(): Config\ComposerUpdateCheckConfig
    {
        $nameMapping = self::PARAMETER_MAPPING;
        $nameMapping['reporters'] = '--reporter';

        $parameters = $this->resolveParameters();

        try {
            return $this->mapper->map(
                Config\ComposerUpdateCheckConfig::class,
                Valinor\Mapper\Source\Source::array($parameters),
            );
        } catch (Valinor\Mapper\MappingError $error) {
            throw new Exception\CommandParametersAreInvalid($error, $nameMapping);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception\ReporterOptionsAreInvalid
     */
    private function resolveParameters(): array
    {
        $parameters = [];

        // Resolve command parameters
        foreach (self::PARAMETER_MAPPING as $configName => $parameterName) {
            if (str_starts_with($parameterName, '--')) {
                $parameters[$configName] = $this->input->getOption(substr($parameterName, 2));
            } else {
                $parameters[$configName] = $this->input->getArgument($parameterName);
            }
        }

        // Resolve reporter configs
        if ([] !== $this->input->getOption('reporter')) {
            /** @var string[] $reporterConfigs */
            $reporterConfigs = $this->input->getOption('reporter');
            $parameters['reporters'] = $this->resolveReporterConfigs($reporterConfigs);
        }

        // Resolve disabled reporters
        if ([] !== $this->input->getOption('disable-reporter')) {
            /** @var string[] $disabledReporters */
            $disabledReporters = $this->input->getOption('disable-reporter');
            foreach ($disabledReporters as $disabledReporter) {
                /* @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible */
                $parameters['reporters'][$disabledReporter]['enabled'] = false;
            }
        }

        return $parameters;
    }

    /**
     * @param string[] $reporterConfigs
     *
     * @return array<non-empty-string, array{enabled: true, options?: array<string, mixed>}>
     *
     * @throws Exception\ReporterIsNotSupported
     * @throws Exception\ReporterOptionsAreInvalid
     */
    private function resolveReporterConfigs(array $reporterConfigs): array
    {
        $reporters = [];

        foreach ($reporterConfigs as $reporterConfig) {
            $configParts = explode(':', $reporterConfig, 2);
            $name = trim($configParts[0]);
            $options = $configParts[1] ?? null;

            if ('' === $name) {
                throw new Exception\ReporterIsNotSupported($name);
            }

            $reporters[$name] = [
                'enabled' => true,
            ];

            if (is_string($options)) {
                $reporters[$name]['options'] = $this->resolveReporterOptions($name, $options);
            }
        }

        return $reporters;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception\ReporterOptionsAreInvalid
     */
    private function resolveReporterOptions(string $name, string $options): array
    {
        try {
            $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new Exception\ReporterOptionsAreInvalid($name);
        }

        if (!is_array($options)) {
            throw new Exception\ReporterOptionsAreInvalid($name);
        }

        if ([] !== array_filter($options, static fn (mixed $key) => !is_string($key), ARRAY_FILTER_USE_KEY)) {
            throw new Exception\ReporterOptionsAreInvalid($name);
        }

        return $options;
    }
}
