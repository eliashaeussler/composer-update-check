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

namespace EliasHaeussler\ComposerUpdateCheck\IO\Formatter;

use Composer\Factory;
use EliasHaeussler\ComposerUpdateCheck\Entity;
use EliasHaeussler\ComposerUpdateCheck\Helper;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

use function realpath;
use function sprintf;

/**
 * GitHubFormatter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class GitHubFormatter implements Formatter
{
    public const FORMAT = 'github';

    public function __construct(
        private ?Console\Style\SymfonyStyle $io = null,
    ) {}

    public function formatResult(Entity\Result\UpdateCheckResult $result): void
    {
        // Early return if IO is missing
        if (null === $this->io) {
            return;
        }

        // Resolve path to composer.json file
        $composerFile = Filesystem\Path::makeRelative(
            (string) realpath(Factory::getComposerFile()),
            Helper\FilesystemHelper::getWorkingDirectory(),
        );

        $outdatedPackages = $result->getOutdatedPackages();
        $excludedPackages = $result->getExcludedPackages();

        foreach ($outdatedPackages as $outdatedPackage) {
            $this->writePackageToOutput(
                $composerFile,
                sprintf('Package %s is outdated', $outdatedPackage->getName()),
                sprintf(
                    // %0A is an encoded newline
                    'Outdated version: %s%%0ANew version: %s%s',
                    $outdatedPackage->getOutdatedVersion(),
                    $outdatedPackage->getNewVersion(),
                    $outdatedPackage->isInsecure() ? '%0A🚨 Package is insecure' : '',
                ),
                'warning',
            );
        }

        foreach ($excludedPackages as $excludedPackage) {
            $this->writePackageToOutput(
                $composerFile,
                sprintf('Package %s was excluded', $excludedPackage->getName()),
                'Package was excluded due to a configured exclude pattern.',
                'notice',
            );
        }
    }

    private function writePackageToOutput(string $composerFile, string $title, string $message, string $severity): void
    {
        $this->io?->writeln(
            // https://docs.github.com/en/actions/using-workflows/workflow-commands-for-github-actions
            sprintf('::%s file=%s,title=%s::%s', $severity, $composerFile, $title, $message),
        );
    }

    public function setIO(Console\Style\SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public static function getFormat(): string
    {
        return self::FORMAT;
    }
}
