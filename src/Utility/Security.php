<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Utility;

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

use Composer\Factory;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use SensioLabs\Security\SecurityChecker;

/**
 * Security
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Security
{
    public static function scan(): array
    {
        $lockFile = static::getLockFile();
        $securityChecker = new SecurityChecker();
        $result = $securityChecker->check($lockFile);
        return json_decode((string) $result, true) ?: [];
    }

    public static function scanAndOverlayResult(UpdateCheckResult $result): UpdateCheckResult
    {
        $scan = static::scan();
        $outdatedPackages = $result->getOutdatedPackages();
        foreach ($outdatedPackages as $outdatedPackage) {
            if (array_key_exists($outdatedPackage->getName(), $scan)) {
                $outdatedPackage->setInsecure(true);
            }
        }
        return $result;
    }

    private static function getLockFile(): string
    {
        $composerFile = Factory::getComposerFile();
        $lockFile = "json" === pathinfo($composerFile, PATHINFO_EXTENSION)
            ? substr($composerFile, 0, -4).'lock'
            : $composerFile . '.lock';
        return $lockFile;
    }
}
