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

namespace EliasHaeussler\ComposerUpdateCheck\Command;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\IO\BufferIO;
use EliasHaeussler\ComposerUpdateCheck\IO\OutputBehavior;
use EliasHaeussler\ComposerUpdateCheck\IO\Style;
use EliasHaeussler\ComposerUpdateCheck\IO\Verbosity;
use EliasHaeussler\ComposerUpdateCheck\Options;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\UpdateChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * UpdateCheckCommand.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateCheckCommand extends BaseCommand
{
    private SymfonyStyle $symfonyStyle;
    private OutputBehavior $behavior;

    protected function configure(): void
    {
        $this->setName('update-check');
        $this->setDescription('Checks your root requirements for available updates.');

        $this->addOption(
            'ignore-packages',
            'i',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Packages to ignore when checking for available updates',
            [],
        );
        $this->addOption(
            'no-dev',
            null,
            InputOption::VALUE_NONE,
            'Disables update check of require-dev packages.',
        );
        $this->addOption(
            'security-scan',
            's',
            InputOption::VALUE_NONE,
            'Run security scan for all outdated packages',
        );
        $this->addOption(
            'json',
            'j',
            InputOption::VALUE_NONE,
            'Format update check as JSON',
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $json = $input->getOption('json');

        $style = $json ? Style::Json : Style::Normal;
        $verbosity = match ($style) {
            Style::Json => Verbosity::Normal,
            default => Verbosity::from($output->getVerbosity()),
        };
        $output->setVerbosity($verbosity->value);

        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->behavior = new OutputBehavior($style, $verbosity, $this->getIO());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Run update check
        $composer = Factory::create(new BufferIO());
        $updateChecker = new UpdateChecker($composer, $this->behavior, Options::fromInput($input));
        $result = $updateChecker->run();

        // Decorate update check result
        $this->decorateResult($result, $updateChecker->getPackageBlacklist(), $input->getOption('security-scan'));

        return self::SUCCESS;
    }

    /**
     * @param string[] $ignoredPackages
     */
    private function decorateResult(
        UpdateCheckResult $result,
        array $ignoredPackages,
        bool $flagInsecurePackages = false,
    ): void {
        $outdatedPackages = $result->getOutdatedPackages();

        // Print message if no packages are outdated
        if ([] === $outdatedPackages) {
            $countSkipped = count($ignoredPackages);
            $message = sprintf(
                'All packages are up to date%s.',
                $countSkipped > 0 ? sprintf(' (skipped %d package%s)', $countSkipped, 1 !== $countSkipped ? 's' : '') : '',
            );
            if (Style::Json === $this->behavior->style) {
                $this->buildJsonReport(['status' => $message], $ignoredPackages);
            } else {
                $this->symfonyStyle->success($message);
            }

            return;
        }

        // Print header
        $statusLabel = 1 === count($outdatedPackages)
            ? '1 package is outdated.'
            : sprintf('%d packages are outdated.', count($outdatedPackages));
        if (Style::Json !== $this->behavior->style) {
            $this->symfonyStyle->warning($statusLabel);
        }

        // Parse table rows
        $tableRows = [];
        foreach ($outdatedPackages as $outdatedPackage) {
            $report = [
                $outdatedPackage->getName(),
                $outdatedPackage->getOutdatedVersion(),
                $outdatedPackage->getNewVersion(),
            ];
            if ($flagInsecurePackages) {
                if (Style::Json !== $this->behavior->style && $outdatedPackage->isInsecure()) {
                    $report[1] .= ' <fg=red;options=bold>insecure</>';
                } elseif (Style::Json === $this->behavior->style) {
                    $report[] = $outdatedPackage->isInsecure();
                }
            }

            $tableRows[] = $report;
        }

        // Print table
        $tableHeader = ['Package', 'Outdated version', 'New version'];
        if (Style::Json !== $this->behavior->style) {
            $this->symfonyStyle->table($tableHeader, $tableRows);
        } else {
            $result = [];
            if ($flagInsecurePackages) {
                $tableHeader[] = 'Insecure';
            }

            foreach ($tableRows as $tableRow) {
                $result[] = array_combine($tableHeader, $tableRow);
            }

            $this->buildJsonReport(['status' => $statusLabel, 'result' => $result], $ignoredPackages);
        }
    }

    /**
     * @param array{status: string, result?: array<int, array<string, mixed>>} $report
     * @param string[]                                                         $ignoredPackages
     */
    private function buildJsonReport(array $report, array $ignoredPackages = []): void
    {
        if ([] !== $ignoredPackages) {
            $report['skipped'] = $ignoredPackages;
        }

        $this->symfonyStyle->writeln(json_encode($report, JSON_THROW_ON_ERROR));
    }
}
