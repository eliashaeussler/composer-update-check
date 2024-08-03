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

namespace EliasHaeussler\ComposerUpdateCheck\Config;

use EliasHaeussler\ComposerUpdateCheck\Helper;
use EliasHaeussler\ComposerUpdateCheck\IO;
use ReflectionClass;

use function array_filter;
use function array_key_exists;
use function get_object_vars;
use function property_exists;

/**
 * ComposerUpdateCheckConfig.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ComposerUpdateCheckConfig
{
    /**
     * @param list<Option\PackageExcludePattern>             $excludePatterns
     * @param array<non-empty-string, Option\ReporterConfig> $reporters
     */
    public function __construct(
        private array $excludePatterns = [],
        private bool $noDev = false,
        private bool $securityScan = false,
        private string $format = IO\Formatter\TextFormatter::FORMAT,
        private array $reporters = [],
    ) {}

    /**
     * @return list<Option\PackageExcludePattern>
     */
    public function getPackageExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    public function excludePackageByName(string $name): self
    {
        $this->excludePatterns[] = Option\PackageExcludePattern::byName($name);

        return $this;
    }

    public function excludePackageByRegularExpression(string $regex): self
    {
        $this->excludePatterns[] = Option\PackageExcludePattern::byRegularExpression($regex);

        return $this;
    }

    public function excludePackageByPattern(Option\PackageExcludePattern $excludePattern): self
    {
        $this->excludePatterns[] = $excludePattern;

        return $this;
    }

    public function areDevPackagesIncluded(): bool
    {
        return !$this->noDev;
    }

    public function includeDevPackages(): self
    {
        $this->noDev = false;

        return $this;
    }

    public function excludeDevPackages(): self
    {
        $this->noDev = true;

        return $this;
    }

    public function shouldPerformSecurityScan(): bool
    {
        return $this->securityScan;
    }

    public function performSecurityScan(): self
    {
        $this->securityScan = true;

        return $this;
    }

    public function skipSecurityScan(): self
    {
        $this->securityScan = false;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function useGitHubFormat(): self
    {
        return $this->setFormat(IO\Formatter\GitHubFormatter::FORMAT);
    }

    public function useGitLabFormat(): self
    {
        return $this->setFormat(IO\Formatter\GitLabFormatter::FORMAT);
    }

    public function useJsonFormat(): self
    {
        return $this->setFormat(IO\Formatter\JsonFormatter::FORMAT);
    }

    public function useTextFormat(): self
    {
        return $this->setFormat(IO\Formatter\TextFormatter::FORMAT);
    }

    /**
     * @return array<non-empty-string, Option\ReporterConfig>
     */
    public function getReporters(): array
    {
        return $this->reporters;
    }

    public function getReporter(string $name): ?Option\ReporterConfig
    {
        return $this->reporters[$name] ?? null;
    }

    /**
     * @return array<non-empty-string, Option\ReporterConfig>
     */
    public function getEnabledReporters(): array
    {
        return array_filter(
            $this->reporters,
            static fn (Option\ReporterConfig $config) => $config->isEnabled(),
        );
    }

    /**
     * @param non-empty-string $name
     */
    public function addReporter(string $name, Option\ReporterConfig $config): self
    {
        $this->reporters[$name] = $config;

        return $this;
    }

    /**
     * @param non-empty-string $name
     */
    public function removeReporter(string $name): self
    {
        unset($this->reporters[$name]);

        return $this;
    }

    /**
     * @param non-empty-string $name
     */
    public function enableReporter(string $name): self
    {
        $config = $this->reporters[$name] ??= new Option\ReporterConfig();
        $config->enable();

        return $this;
    }

    /**
     * @param non-empty-string $name
     */
    public function disableReporter(string $name): self
    {
        $config = $this->reporters[$name] ?? new Option\ReporterConfig();
        $config->disable();

        return $this;
    }

    public function merge(self $other): self
    {
        $parameters = $this->toArray(true);

        Helper\ArrayHelper::mergeRecursive($parameters, $other->toArray(true));

        foreach ($parameters as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }

        return $this;
    }

    /**
     * @return array<non-empty-string, mixed>
     */
    public function toArray(bool $omitDefaultValues = false): array
    {
        /** @var array<non-empty-string, mixed> $config */
        $config = get_object_vars($this);

        if (!$omitDefaultValues) {
            return $config;
        }

        $reflection = new ReflectionClass(self::class);
        $parameters = $reflection->getConstructor()?->getParameters() ?? [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (!$parameter->isOptional()) {
                continue;
            }

            if (array_key_exists($name, $config) && $config[$name] === $parameter->getDefaultValue()) {
                unset($config[$name]);
            }
        }

        return $config;
    }
}
