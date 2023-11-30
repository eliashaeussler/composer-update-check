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

namespace EliasHaeussler\ComposerUpdateCheck\IO\Formatter;

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\Package;
use EliasHaeussler\ComposerUpdateCheck\UpdateCheckResult;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

use function count;
use function sprintf;

/**
 * TextFormatter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class TextFormatter implements Formatter
{
    public const FORMAT = 'text';

    public function __construct(
        private (OutputInterface&StyleInterface)|null $io = null,
    ) {}

    public function formatResult(UpdateCheckResult $result): void
    {
        // Early return if IO is missing
        if (null === $this->io) {
            return;
        }

        $outdatedPackages = $result->getOutdatedPackages();
        $excludedPackages = $result->getExcludedPackages();
        $numberOfOutdatedPackages = count($outdatedPackages);

        // Print message if no packages are outdated
        if ([] === $outdatedPackages) {
            $this->renderUpToDateMessage($excludedPackages);

            return;
        }

        // Print header
        if (1 === $numberOfOutdatedPackages) {
            $this->io->warning('1 package is outdated.');
        } else {
            $this->io->warning(
                sprintf('%d packages are outdated.', $numberOfOutdatedPackages),
            );
        }

        $this->renderTable($outdatedPackages);
    }

    /**
     * @param list<Package> $excludedPackages
     */
    private function renderUpToDateMessage(array $excludedPackages): void
    {
        $numberOfExcludedPackages = count($excludedPackages);

        if ($numberOfExcludedPackages > 0) {
            $additionalInformation = sprintf(
                ' (skipped %d package%s)',
                $numberOfExcludedPackages,
                1 !== $numberOfExcludedPackages ? 's' : '',
            );
        } else {
            $additionalInformation = '';
        }

        $this->io->success(
            sprintf('All packages are up to date%s.', $additionalInformation),
        );
    }

    /**
     * @param list<OutdatedPackage> $outdatedPackages
     */
    private function renderTable(array $outdatedPackages): void
    {
        $tableRows = [];

        // Parse table rows
        foreach ($outdatedPackages as $outdatedPackage) {
            $report = [
                $outdatedPackage->getName(),
                $outdatedPackage->getOutdatedVersion()->get(),
                $outdatedPackage->getNewVersion()->get(),
            ];

            if ($outdatedPackage->isInsecure()) {
                $report[1] .= ' <fg=red;options=bold>insecure</>';
            }

            $tableRows[] = $report;
        }

        // Print table
        $this->io->table(['Package', 'Outdated version', 'New version'], $tableRows);
    }

    public function setIO(OutputInterface&StyleInterface $io): void
    {
        $this->io = $io;
    }

    public static function getFormat(): string
    {
        return self::FORMAT;
    }
}
