<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit;

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

use EliasHaeussler\ComposerUpdateCheck\Utility\Composer;

/**
 * ExpectedCommandOutputTrait
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
trait ExpectedCommandOutputTrait
{
    private function getExpectedCommandOutput(string $package = null, string $outdated = null, string $new = null): string
    {
        $isLegacyPlatform = Composer::getMajorVersion() < 2;
        $output = $isLegacyPlatform ? ' - Updating' : ' - Upgrading';

        // Early return if no package is specified
        if ($package === null) {
            return $output;
        }

        $output .= ' ' . $package;

        // Early return if package versions are not completely specified
        if ($outdated === null || $new === null || trim($outdated) === '' || trim($new) === '') {
            return $output;
        }

        // Build expected command output
        if ($isLegacyPlatform) {
            return sprintf('%s (%s) to %s (%s)', $output, $outdated, $package, $new);
        }
        return sprintf('%s (%s => %s)', $output, $outdated, $new);
    }
}
