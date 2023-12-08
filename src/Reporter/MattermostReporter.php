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

namespace EliasHaeussler\ComposerUpdateCheck\Reporter;

use Composer\Composer;
use Composer\IO;
use EliasHaeussler\ComposerUpdateCheck\Entity;
use GuzzleHttp\Client;
use GuzzleHttp\Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use Symfony\Component\OptionsResolver;

/**
 * MattermostReporter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class MattermostReporter implements Reporter
{
    public const NAME = 'mattermost';

    private readonly OptionsResolver\OptionsResolver $resolver;

    public function __construct(
        private readonly Client $client,
        private readonly Composer $composer,
        private readonly IO\IOInterface $io,
    ) {
        $this->resolver = $this->createOptionsResolver();
    }

    public function report(Entity\Result\UpdateCheckResult $result, array $options): bool
    {
        ['url' => $url, 'channel' => $channel, 'username' => $username] = $this->resolver->resolve($options);

        // Resolve root package name from composer.json
        $rootPackageName = $this->composer->getPackage()->getName();
        if ('__root__' === $rootPackageName) {
            $rootPackageName = null;
        }

        // Create report
        $report = Entity\Report\MattermostReport::create($channel, $username, $result, $rootPackageName);

        // Send report
        try {
            $this->io->writeError('📤 Sending report to Mattermost... ', false, IO\IOInterface::VERBOSE);

            $response = $this->client->post($url, [
                RequestOptions::JSON => $report,
            ]);
            $successful = 200 === $response->getStatusCode();
        } catch (Exception\GuzzleException) {
            $successful = false;
        }

        if ($successful) {
            $this->io->writeError('<info>Done</info>', true, IO\IOInterface::VERBOSE);
        } else {
            $this->io->writeError('<error>Failed</error>', true, IO\IOInterface::VERBOSE);
        }

        return $successful;
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    private function createOptionsResolver(): OptionsResolver\OptionsResolver
    {
        $resolver = new OptionsResolver\OptionsResolver();

        $resolver->define('channel')
            ->allowedTypes('string')
            ->required()
        ;

        $resolver->define('url')
            ->allowedTypes('string')
            ->required()
            ->normalize(
                static fn (OptionsResolver\OptionsResolver $resolver, string $url) => new Psr7\Uri($url),
            )
        ;

        $resolver->define('username')
            ->allowedTypes('string', 'null')
            ->default(null)
        ;

        return $resolver;
    }
}
