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
use Composer\IO\IOInterface;
use EliasHaeussler\ComposerUpdateCheck\Entity\Report\MattermostReport;
use EliasHaeussler\ComposerUpdateCheck\Entity\Result\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Exception\ReporterOptionsAreInvalid;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MattermostReporter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class MattermostReporter implements Reporter
{
    public const NAME = 'mattermost';

    private readonly OptionsResolver $resolver;
    private ?UriInterface $url = null;
    private ?string $channel = null;
    private ?string $username = null;

    public function __construct(
        private readonly Client $client,
        private readonly Composer $composer,
        private readonly IOInterface $io,
    ) {
        $this->resolver = $this->createOptionsResolver();
    }

    /**
     * @throws ReporterOptionsAreInvalid
     */
    public function report(UpdateCheckResult $result): bool
    {
        // Validate options
        if (null === $this->url || null === $this->channel) {
            throw new ReporterOptionsAreInvalid(self::NAME);
        }

        // Resolve root package name from composer.json
        $rootPackageName = $this->composer->getPackage()->getName();
        if ('__root__' === $rootPackageName) {
            $rootPackageName = null;
        }

        // Create report
        $report = MattermostReport::create($this->channel, $this->username, $result, $rootPackageName);

        // Send report
        try {
            $this->io->write('📤 Sending report to Mattermost...', true, IOInterface::VERBOSE);

            $response = $this->client->post($this->url, [
                RequestOptions::JSON => $report,
            ]);
        } catch (GuzzleException) {
            return false;
        }

        return 200 === $response->getStatusCode();
    }

    public function setOptions(array $options): void
    {
        ['url' => $this->url, 'channel' => $this->channel, 'username' => $this->username] = $this->resolver->resolve($options);
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    private function createOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->define('channel')
            ->allowedTypes('string')
            ->required()
        ;

        $resolver->define('url')
            ->allowedTypes('string')
            ->required()
            ->normalize(
                static fn (OptionsResolver $resolver, string $url) => new Uri($url),
            )
        ;

        $resolver->define('username')
            ->allowedTypes('string')
        ;

        return $resolver;
    }
}
