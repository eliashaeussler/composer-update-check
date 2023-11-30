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

namespace EliasHaeussler\ComposerUpdateCheck\Configuration;

use EliasHaeussler\ComposerUpdateCheck\Configuration\Options\PackageExcludePattern;
use EliasHaeussler\ComposerUpdateCheck\IO\Formatter\TextFormatter;

/**
 * ComposerUpdateCheckConfig.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ComposerUpdateCheckConfig
{
    /**
     * @param list<PackageExcludePattern> $excludePatterns
     */
    public function __construct(
        private array $excludePatterns = [],
        private bool $includeDevPackages = true,
        private bool $performSecurityScan = false,
        private string $format = TextFormatter::FORMAT,
    ) {}

    public function excludePackageByName(string $name): self
    {
        $this->excludePatterns[] = PackageExcludePattern::name($name);

        return $this;
    }

    public function excludePackageByRegex(string $regex): self
    {
        $this->excludePatterns[] = PackageExcludePattern::regex($regex);

        return $this;
    }

    public function excludePackageByPattern(PackageExcludePattern $excludePattern): self
    {
        $this->excludePatterns[] = $excludePattern;

        return $this;
    }

    /**
     * @return list<PackageExcludePattern>
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
}
