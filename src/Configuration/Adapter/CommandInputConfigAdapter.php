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
use EliasHaeussler\ComposerUpdateCheck\Exception;
use JsonException;
use Symfony\Component\Console;

use function explode;
use function is_array;
use function is_string;
use function json_decode;

/**
 * CommandInputConfigAdapter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class CommandInputConfigAdapter implements ConfigAdapter
{
    public function __construct(
        private readonly Console\Input\InputInterface $input,
    ) {}

    /**
     * @throws Exception\ReporterOptionsAreInvalid
     */
    public function resolve(): Configuration\ComposerUpdateCheckConfig
    {
        $config = new Configuration\ComposerUpdateCheckConfig();

        if ($this->input->hasOption('exclude-packages')) {
            /** @var array<string> $excludePatterns */
            $excludePatterns = $this->input->getOption('exclude-packages');

            foreach ($excludePatterns as $pattern) {
                $excludePattern = Configuration\Options\PackageExcludePattern::create($pattern);
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

        if ($this->input->hasOption('reporter') && is_array($this->input->getOption('reporter'))) {
            $this->enableReporters($config, $this->input->getOption('reporter'));
        }

        if ($this->input->hasOption('disable-reporter') && is_array($this->input->getOption('disable-reporter'))) {
            foreach ($this->input->getOption('disable-reporter') as $name) {
                $config->disableReporter($name);
            }
        }

        return $config;
    }

    /**
     * @param array<string> $reporters
     *
     * @throws Exception\ReporterOptionsAreInvalid
     */
    private function enableReporters(Configuration\ComposerUpdateCheckConfig $config, array $reporters): void
    {
        foreach ($reporters as $reporterConfig) {
            $configParts = explode(':', $reporterConfig, 2);
            $name = $configParts[0];
            $options = $configParts[1] ?? [];

            if (is_string($options)) {
                $options = $this->parseReporterOptions($name, $options);
            } else {
                $options = [];
            }

            $config->enableReporter($name, $options);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception\ReporterOptionsAreInvalid
     */
    private function parseReporterOptions(string $name, string $json): array
    {
        try {
            $options = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($options)) {
                throw new Exception\ReporterOptionsAreInvalid($name);
            }

            return $options;
        } catch (JsonException) {
            throw new Exception\ReporterOptionsAreInvalid($name);
        }
    }
}
