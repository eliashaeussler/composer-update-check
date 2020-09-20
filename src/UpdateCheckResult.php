<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * UpdateCheckResult
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UpdateCheckResult
{
    protected const COMMAND_OUTPUT_PATTERN =
        '#^' .
            '\\s*- Updating ' .
            '(?P<name>[^\\s]+) \\(' .
                '(?P<outdated>[^)]+)' .
            '\\) to [^\\s]+ \\(' .
                '(?P<new>[^)]+)' .
            '\\)' .
        '$#';

    /**
     * @var OutdatedPackage[]
     */
    private $outdatedPackages;

    public function __construct(array $outdatedPackages)
    {
        $this->outdatedPackages = $outdatedPackages;
        $this->validateOutdatedPackages();
    }

    /**
     * @return OutdatedPackage[]
     */
    public function getOutdatedPackages(): array
    {
        return $this->outdatedPackages;
    }

    public static function fromCommandOutput(string $output): self
    {
        $outputParts = explode(PHP_EOL, $output);
        $packages = array_filter(array_map([static::class, 'parseCommandOutput'], $outputParts));
        return new static($packages);
    }

    public static function parseCommandOutput(string $output): ?OutdatedPackage
    {
        if (!preg_match(static::COMMAND_OUTPUT_PATTERN, $output, $matches)) {
            return null;
        }
        $packageName = $matches['name'];
        $outdatedVersion = $matches['outdated'];
        $newVersion = $matches['new'];
        return new OutdatedPackage($packageName, $outdatedVersion, $newVersion);
    }

    private function validateOutdatedPackages(): void
    {
        foreach ($this->outdatedPackages as $key => $outdatedPackage) {
            if (!($outdatedPackage instanceof OutdatedPackage)) {
                throw new \InvalidArgumentException(
                    sprintf('Outdated package #%s must be an instance of "%s".', $key, OutdatedPackage::class),
                    1600276584
                );
            }
        }
    }
}
