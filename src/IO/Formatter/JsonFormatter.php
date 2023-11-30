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

use EliasHaeussler\ComposerUpdateCheck\Entity\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\Package;
use EliasHaeussler\ComposerUpdateCheck\UpdateCheckResult;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

use function count;
use function json_encode;
use function sprintf;

/**
 * JsonFormatter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class JsonFormatter implements Formatter
{
    public const FORMAT = 'json';

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

        // Print message if no packages are outdated
        if ([] === $outdatedPackages) {
            $this->renderUpToDateMessage($excludedPackages);

            return;
        }

        $this->renderTable($outdatedPackages, $excludedPackages);
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

        $json = [
            'status' => sprintf('All packages are up to date%s.', $additionalInformation),
        ];

        if ([] !== $excludedPackages) {
            $json['excludedPackages'] = $excludedPackages;
        }

        $this->renderJson($json);
    }

    /**
     * @param list<OutdatedPackage> $outdatedPackages
     * @param list<Package>         $excludedPackages
     */
    private function renderTable(array $outdatedPackages, array $excludedPackages): void
    {
        $numberOfOutdatedPackages = count($outdatedPackages);
        $hasInsecurePackages = false;

        // Create status label
        if (1 === $numberOfOutdatedPackages) {
            $statusLabel = '1 package is outdated.';
        } else {
            $statusLabel = sprintf('%d packages are outdated.', $numberOfOutdatedPackages);
        }

        // Print table
        $tableHeader = ['name', 'outdatedVersion', 'newVersion'];
        $tableRows = $this->parseTableRows($outdatedPackages, $hasInsecurePackages);
        $result = [];

        if ($hasInsecurePackages) {
            $tableHeader[] = 'insecure';
        }

        foreach ($tableRows as $tableRow) {
            $result[] = array_combine($tableHeader, $tableRow);
        }

        $json = [
            'status' => $statusLabel,
            'outdatedPackages' => $result,
        ];

        if ([] !== $excludedPackages) {
            $json['excludedPackages'] = $excludedPackages;
        }

        $this->renderJson($json);
    }

    /**
     * @param list<OutdatedPackage> $outdatedPackages
     *
     * @return list<array{0: non-empty-string, 1: string, 2: string, 3?: bool}>
     */
    private function parseTableRows(array $outdatedPackages, bool &$hasInsecurePackages = false): array
    {
        $tableRows = [];

        foreach ($outdatedPackages as $outdatedPackage) {
            $report = [
                $outdatedPackage->getName(),
                $outdatedPackage->getOutdatedVersion()->get(),
                $outdatedPackage->getNewVersion()->get(),
            ];

            if ($outdatedPackage->isInsecure()) {
                $report[] = $outdatedPackage->isInsecure();
                $hasInsecurePackages = true;
            }

            $tableRows[] = $report;
        }

        return $tableRows;
    }

    /**
     * @param array<string, mixed> $json
     */
    private function renderJson(array $json): void
    {
        // Early return if output is quiet
        if ($this->io->isQuiet()) {
            return;
        }

        $flags = JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

        // Pretty-print JSON on verbose output
        if ($this->io->isVerbose()) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $this->io->writeln(json_encode($json, $flags));
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
