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
use CuyZ\Valinor\Mapper\Tree\Message\Messages;
use CuyZ\Valinor\Mapper\Tree\Message\NodeMessage;
use EliasHaeussler\ComposerUpdateCheck\Configuration\Adapter\ChainedConfigAdapter;
use EliasHaeussler\ComposerUpdateCheck\Configuration\Adapter\CommandInputConfigAdapter;
use EliasHaeussler\ComposerUpdateCheck\Configuration\Adapter\ConfigAdapterFactory;
use EliasHaeussler\ComposerUpdateCheck\Configuration\ComposerUpdateCheckConfig;
use EliasHaeussler\ComposerUpdateCheck\Exception\ConfigFileHasErrors;
use EliasHaeussler\ComposerUpdateCheck\IO\Formatter\FormatterFactory;
use EliasHaeussler\ComposerUpdateCheck\UpdateChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_map;
use function sprintf;

/**
 * UpdateCheckCommand.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateCheckCommand extends BaseCommand
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly FormatterFactory $formatterFactory,
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
            InputOption::VALUE_REQUIRED,
            'Path to configuration file, can be in JSON, PHP oder YAML format',
        );
        $this->addOption(
            'ignore-packages',
            'i',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Packages to ignore when checking for available updates',
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
            'format',
            'f',
            InputOption::VALUE_REQUIRED,
            'Format to display update check results',
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->formatterFactory->setIO($this->io);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $config = $this->resolveConfiguration($input);
        } catch (ConfigFileHasErrors $exception) {
            $this->displayMappingErrors($exception);

            return self::FAILURE;
        }

        $formatter = $this->formatterFactory->make($config->getFormat());
        $result = $this->updateChecker->run($config);
        $formatter->formatResult($result);

        return self::SUCCESS;
    }

    private function resolveConfiguration(InputInterface $input): ComposerUpdateCheckConfig
    {
        $filename = $input->getOption('config');
        $configAdapter = new CommandInputConfigAdapter($input);

        if (null !== $filename && '' !== $filename) {
            $configAdapterFactory = new ConfigAdapterFactory();
            $configAdapter = new ChainedConfigAdapter([
                $configAdapterFactory->make($filename),
                $configAdapter,
            ]);
        }

        return $configAdapter->resolve();
    }

    private function displayMappingErrors(ConfigFileHasErrors $exception): void
    {
        $errors = Messages::flattenFromNode($exception->error->node())->errors();

        $this->io->error($exception->getMessage());
        $this->io->writeln('The following errors occurred:');
        $this->io->listing(
            array_map($this->formatErrorMessage(...), $errors->toArray()),
        );
    }

    private function formatErrorMessage(NodeMessage $message): string
    {
        return sprintf('<comment>%s</comment>: %s', $message->node()->path(), $message->toString());
    }
}
