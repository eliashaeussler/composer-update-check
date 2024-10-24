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

namespace EliasHaeussler\ComposerUpdateCheck\Configuration;

use EliasHaeussler\ComposerUpdateCheck\IO;

/**
 * ComposerUpdateCheckConfig.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ComposerUpdateCheckConfig
{
    /**
     * @param list<Options\PackageExcludePattern> $excludePatterns
     * @param array<string, array<string, mixed>> $reporters
     */
    public function __construct(
        private array $excludePatterns = [],
        private bool $includeDevPackages = true,
        private bool $performSecurityScan = false,
        private string $format = IO\Formatter\TextFormatter::FORMAT,
        private array $reporters = [],
    ) {}

    public function excludePackageByName(string $name): self
    {
        $this->excludePatterns[] = Options\PackageExcludePattern::byName($name);

        return $this;
    }

    public function excludePackageByRegularExpression(string $regex): self
    {
        $this->excludePatterns[] = Options\PackageExcludePattern::byRegularExpression($regex);

        return $this;
    }

    public function excludePackageByPattern(Options\PackageExcludePattern $excludePattern): self
    {
        $this->excludePatterns[] = $excludePattern;

        return $this;
    }

    /**
     * @return list<Options\PackageExcludePattern>
     */
    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    public function includeDevPackages(): self
    {
        $this->includeDevPackages = true;

        return $this;
    }

    public function excludeDevPackages(): self
    {
        $this->includeDevPackages = false;

        return $this;
    }

    public function areDevPackagesIncluded(): bool
    {
        return $this->includeDevPackages;
    }

    public function performSecurityScan(): self
    {
        $this->performSecurityScan = true;

        return $this;
    }

    public function skipSecurityScan(): self
    {
        $this->performSecurityScan = false;

        return $this;
    }

    public function shouldPerformSecurityScan(): bool
    {
        return $this->performSecurityScan;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function enableReporter(string $name, array $options = []): self
    {
        $this->reporters[$name] = $options;

        return $this;
    }

    public function disableReporter(string $name): self
    {
        unset($this->reporters[$name]);

        return $this;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getReporters(): array
    {
        return $this->reporters;
    }
}
