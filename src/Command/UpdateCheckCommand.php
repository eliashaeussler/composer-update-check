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

namespace EliasHaeussler\ComposerUpdateCheck\Command;

use Composer\Command;
use CuyZ\Valinor;
use EliasHaeussler\ComposerUpdateCheck\Configuration;
use EliasHaeussler\ComposerUpdateCheck\Exception;
use EliasHaeussler\ComposerUpdateCheck\IO;
use EliasHaeussler\ComposerUpdateCheck\UpdateChecker;
use Symfony\Component\Console;

use function array_map;
use function array_unshift;
use function sprintf;

/**
 * UpdateCheckCommand.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateCheckCommand extends Command\BaseCommand
{
    private Console\Style\SymfonyStyle $io;

    public function __construct(
        private readonly UpdateChecker $updateChecker,
    ) {
        parent::__construct('update-check');
    }

    protected function configure(): void
    {
        $this->setDescription('Checks your root requirements for available updates.');

        $this->addOption(
            'config',
            'c',
            Console\Input\InputOption::VALUE_REQUIRED,
            'Path to configuration file, can be in JSON, PHP oder YAML format',
        );
        $this->addOption(
            'exclude-packages',
            'e',
            Console\Input\InputOption::VALUE_REQUIRED | Console\Input\InputOption::VALUE_IS_ARRAY,
            'Packages to exclude when checking for available updates',
        );
        $this->addOption(
            'no-dev',
            null,
            Console\Input\InputOption::VALUE_NONE,
            'Disables update check of require-dev packages.',
        );
        $this->addOption(
            'security-scan',
            's',
            Console\Input\InputOption::VALUE_NONE,
            'Run security scan for all outdated packages',
        );
        $this->addOption(
            'format',
            'f',
            Console\Input\InputOption::VALUE_REQUIRED,
            'Format to display update check results',
        );
        $this->addOption(
            'reporter',
            'r',
            Console\Input\InputOption::VALUE_REQUIRED | Console\Input\InputOption::VALUE_IS_ARRAY,
            'Enable given reporters, may additionally contain a JSON-encoded string of reporter options, separated by colon (:)',
        );
        $this->addOption(
            'disable-reporter',
            'R',
            Console\Input\InputOption::VALUE_REQUIRED | Console\Input\InputOption::VALUE_IS_ARRAY,
            'Disable given reporters (even if they were enabled with the --reporter option)',
        );
    }

    protected function initialize(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        $this->io = new Console\Style\SymfonyStyle($input, $output);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        try {
            $config = $this->resolveConfiguration($input);
        } catch (Exception\ConfigFileIsInvalid $exception) {
            $this->displayConfigError($exception);

            return self::FAILURE;
        } catch (Exception\ConfigFileHasErrors $exception) {
            $this->displayMappingErrors($exception);

            return self::FAILURE;
        }

        $formatter = (new IO\Formatter\FormatterFactory($this->io))->make($config->getFormat());
        $result = $this->updateChecker->run($config);
        $formatter->formatResult($result);

        return self::SUCCESS;
    }

    private function resolveConfiguration(Console\Input\InputInterface $input): Configuration\ComposerUpdateCheckConfig
    {
        $filename = $input->getOption('config');
        $adapters = [
            new Configuration\Adapter\CommandInputConfigAdapter($input),
            new Configuration\Adapter\EnvironmentVariablesConfigAdapter(),
        ];

        if (null !== $filename && '' !== $filename) {
            $configAdapterFactory = new Configuration\Adapter\ConfigAdapterFactory();

            array_unshift($adapters, $configAdapterFactory->make($filename));
        }

        $configAdapter = new Configuration\Adapter\ChainedConfigAdapter($adapters);

        return $configAdapter->resolve();
    }

    private function displayConfigError(Exception\ConfigFileIsInvalid $exception): void
    {
        $this->io->error($exception->getMessage());

        if (null !== $exception->getPrevious()) {
            $this->io->writeln([
                'The following error occurred:',
                ' * '.$exception->getPrevious()->getMessage(),
            ]);
        }
    }

    private function displayMappingErrors(Exception\ConfigFileHasErrors $exception): void
    {
        $errors = Valinor\Mapper\Tree\Message\Messages::flattenFromNode($exception->error->node())->errors();

        $this->io->error($exception->getMessage());
        $this->io->writeln('The following errors occurred:');
        $this->io->listing(
            array_map($this->formatErrorMessage(...), $errors->toArray()),
        );
    }

    private function formatErrorMessage(Valinor\Mapper\Tree\Message\NodeMessage $message): string
    {
        return sprintf('<comment>%s</comment>: %s', $message->node()->path(), $message->toString());
    }
}
