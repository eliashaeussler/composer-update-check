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
use Composer\Command\UpdateCommand;
use Composer\IO\ConsoleIO;
use Composer\Plugin\PluginEvents;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;
use EliasHaeussler\ComposerUpdateCheck\UpdateCheckResult;
use Spatie\Emoji\Emoji;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->symfonyStyle = new SymfonyStyle($this->input, $this->output);

        // Prepare command options
        $ignoredPackages = $input->getOption('ignore-packages');
        $noDev = $input->getOption('no-dev');
        $command = $this->getApplication()->find('update');

        // Throw exception if update command is not available
        if (!($command instanceof UpdateCommand)) {
            throw new \RuntimeException('Native update command is not available.', 1600274132);
        }

        // Resolve packages to be checked
        $packages = null;
        if ($ignoredPackages !== [] || $noDev) {
            $this->symfonyStyle->writeln(Emoji::package() . ' Resolving packages...');
            $packages = $this->resolvePackagesForUpdateCheck($ignoredPackages ?? [], !$noDev);
        }

        // Run update check
        $result = $this->runUpdateCheck($command, $packages);
        $this->dispatchPostUpdateCheckEvent($result);
        $this->decorateResult($result);

        return 0;
    }

    private function runUpdateCheck(UpdateCommand $command, array $packages = null): UpdateCheckResult
    {
        // Prepare command arguments
        $arguments = [
            '--dry-run' => true,
            '--root-reqs' => true,
            '--no-ansi' => true,
            '--ignore-platform-reqs' => true,
        ];
        if ($packages !== null) {
            $arguments['packages'] = $packages;
        }

        // Prepare IO
        $output = new BufferedOutput();
        $command->setIO(new ConsoleIO($this->input, $output, new HelperSet()));

        // Run update command
        $this->symfonyStyle->writeln(Emoji::hourglassNotDone() . ' Checking for outdated packages...');
        $input = new ArrayInput($arguments);
        $result = $command->run($input, new NullOutput());

        // Handle command failures
        if ($result > 0) {
            $this->symfonyStyle->writeln($output->fetch());
            throw new \RuntimeException(
                sprintf('Error during update check. Exit code from "composer update": %d', $result),
                1600278536
            );
        }

        return UpdateCheckResult::fromCommandOutput($output->fetch());
    }

    private function decorateResult(UpdateCheckResult $result): void
    {
        $outdatedPackages = $result->getOutdatedPackages();

        // Print message if no packages are outdated
        if ($outdatedPackages === []) {
            $this->symfonyStyle->success('All packages are up to date.');
            return;
        }

        // Print header
        if (count($outdatedPackages) === 1) {
            $this->symfonyStyle->warning('1 package is outdated.');
        } else {
            $this->symfonyStyle->warning(sprintf('%d packages are outdated.', count($outdatedPackages)));
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
        $this->symfonyStyle->table($tableHeader, $tableRows);
    }

    private function resolvePackagesForUpdateCheck(array $ignoredPackages, bool $includeDevPackages): array
    {
        $rootPackage = $this->getComposer()->getPackage();
        $requiredPackages = array_keys($rootPackage->getRequires());
        if ($includeDevPackages) {
            $requiredDevPackages = array_keys($rootPackage->getDevRequires());
            $requiredPackages = array_merge($requiredPackages, $requiredDevPackages);
        } else {
            $this->symfonyStyle->writeln(Emoji::prohibited() . ' Skipped dev-requirements');
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
            $this->symfonyStyle->writeln(sprintf('%s Skipped "%s"', Emoji::prohibited(), $package));
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
