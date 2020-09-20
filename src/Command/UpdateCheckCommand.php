<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Command;

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

use Composer\Command\BaseCommand;
use Composer\Plugin\PluginEvents;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;
use EliasHaeussler\ComposerUpdateCheck\Installer;
use EliasHaeussler\ComposerUpdateCheck\UpdateCheckResult;
use Spatie\Emoji\Emoji;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * UpdateCheckCommand
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UpdateCheckCommand extends BaseCommand
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var bool
     */
    private $json = false;

    /**
     * @var string[]
     */
    private $ignoredPackages = [];

    protected function configure(): void
    {
        $this->setName('update-check');
        $this->setDescription('Checks your root requirements for available updates.');

        $this->addOption(
            'ignore-packages',
            'i',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Packages to ignore when checking for available updates',
            []
        );
        $this->addOption(
            'no-dev',
            null,
            InputOption::VALUE_NONE,
            'Disables update check of require-dev packages.'
        );
        $this->addOption(
            'json',
            'j',
            InputOption::VALUE_NONE,
            'Format update check as JSON'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->symfonyStyle = new SymfonyStyle($this->input, $this->output);
        $this->json = $input->getOption('json');

        // Prepare command options
        $ignoredPackages = $input->getOption('ignore-packages');
        $noDev = $input->getOption('no-dev');

        // Resolve packages to be checked
        if (!$this->json) {
            $this->symfonyStyle->writeln(Emoji::package() . ' Resolving packages...');
        }
        $packages = $this->resolvePackagesForUpdateCheck($ignoredPackages, !$noDev);

        // Run update check
        $result = $this->runUpdateCheck($packages);
        $this->dispatchPostUpdateCheckEvent($result);
        $this->decorateResult($result);

        return 0;
    }

    private function runUpdateCheck(array $packages): UpdateCheckResult
    {
        // Early return if no packages are listed for update check
        if ($packages === []) {
            return new UpdateCheckResult([]);
        }

        // Ensure dependencies are installed
        $this->installDependencies();

        // Run Composer installer
        if (!$this->json) {
            $this->symfonyStyle->writeln(Emoji::hourglassNotDone() . ' Checking for outdated packages...');
        }
        $result = Installer::runUpdate($packages, $this->getComposer());

        // Handle installer failures
        if ($result > 0) {
            $this->symfonyStyle->writeln(Installer::getLastOutput());
            throw new \RuntimeException(
                sprintf('Error during update check. Exit code from Composer installer: %d', $result),
                1600278536
            );
        }

        return UpdateCheckResult::fromCommandOutput(Installer::getLastOutput());
    }

    private function installDependencies(): void
    {
        // Run Composer installer
        $result = Installer::runInstall($this->getComposer());

        // Handle installer failures
        if ($result > 0) {
            $this->symfonyStyle->writeln(Installer::getLastOutput());
            throw new \RuntimeException(
                sprintf('Error during dependency install. Exit code from Composer installer: %d', $result),
                1600614218
            );
        }
    }

    private function decorateResult(UpdateCheckResult $result): void
    {
        $outdatedPackages = $result->getOutdatedPackages();

        // Print message if no packages are outdated
        if ($outdatedPackages === []) {
            $countSkipped = count($this->ignoredPackages);
            $message = sprintf(
                'All packages are up to date%s.',
                $countSkipped > 0
                    ? sprintf(' (skipped %d package%s)', $countSkipped, $countSkipped !== 1 ? 's' : '')
                    : ''
            );
            $this->json ? $this->buildJsonReport(['status' => $message]) : $this->symfonyStyle->success($message);
            return;
        }

        // Print header
        $statusLabel = count($outdatedPackages) === 1
            ? '1 package is outdated.'
            : sprintf('%d packages are outdated.', count($outdatedPackages));
        if (!$this->json) {
            $this->symfonyStyle->warning($statusLabel);
        }

        // Parse table rows
        $tableRows = [];
        foreach ($outdatedPackages as $outdatedPackage) {
            $tableRows[] = [
                $outdatedPackage->getName(),
                $outdatedPackage->getOutdatedVersion(),
                $outdatedPackage->getNewVersion(),
            ];
        }

        // Print table
        $tableHeader = ['Package', 'Outdated version', 'New version'];
        if (!$this->json) {
            $this->symfonyStyle->table($tableHeader, $tableRows);
        } else {
            $result = [];
            foreach ($tableRows as $tableRow) {
                $result[] = array_combine($tableHeader, $tableRow);
            }
            $this->buildJsonReport([
                'status' => $statusLabel,
                'result' => $result,
            ]);
        }
    }

    private function buildJsonReport(array $report): void
    {
        if ($this->ignoredPackages !== []) {
            $report['skipped'] = $this->ignoredPackages;
        }
        $this->symfonyStyle->writeln(json_encode($report));
    }

    private function resolvePackagesForUpdateCheck(array $ignoredPackages, bool $includeDevPackages): array
    {
        $rootPackage = $this->getComposer()->getPackage();
        $requiredPackages = array_keys($rootPackage->getRequires());
        $requiredDevPackages = array_keys($rootPackage->getDevRequires());
        if ($includeDevPackages) {
            $requiredPackages = array_merge($requiredPackages, $requiredDevPackages);
        } else {
            $this->ignoredPackages = array_merge($this->ignoredPackages, $requiredDevPackages);
            if (!$this->json) {
                $this->symfonyStyle->writeln(Emoji::prohibited() . ' Skipped dev-requirements');
            }
        }
        foreach ($ignoredPackages as $ignoredPackage) {
            $requiredPackages = $this->removeByIgnorePattern($ignoredPackage, $requiredPackages);
        }
        return $requiredPackages;
    }

    private function removeByIgnorePattern(string $pattern, array $packages): array
    {
        return array_filter($packages, function (string $package) use ($pattern) {
            if (!fnmatch($pattern, $package)) {
                return true;
            }
            if (!$this->json) {
                $this->symfonyStyle->writeln(sprintf('%s Skipped "%s"', Emoji::prohibited(), $package));
            }
            $this->ignoredPackages[] = $package;
            return false;
        });
    }

    private function dispatchPostUpdateCheckEvent(UpdateCheckResult $result): void
    {
        $commandEvent = new PostUpdateCheckEvent(
            PluginEvents::COMMAND,
            'update-check',
            $this->input,
            $this->output,
            [],
            [],
            $result
        );
        $this->getComposer()->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
    }
}
