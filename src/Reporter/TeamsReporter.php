<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020-2026 Elias Häußler <elias@haeussler.dev>
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

use Composer\IO;
use EliasHaeussler\ComposerUpdateCheck\Entity;
use EliasHaeussler\TaskRunner;
use GuzzleHttp\Client;
use GuzzleHttp\Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use Symfony\Component\OptionsResolver;

use function assert;
use function is_string;

/**
 * TeamsReporter.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final readonly class TeamsReporter implements Reporter
{
    public const NAME = 'teams';

    private OptionsResolver\OptionsResolver $resolver;
    private TaskRunner\TaskRunner $taskRunner;

    public function __construct(
        private Client $client,
        private IO\IOInterface $io,
    ) {
        $this->resolver = $this->createOptionsResolver();
        $this->taskRunner = new TaskRunner\TaskRunner($this->io);
    }

    public function report(Entity\Result\UpdateCheckResult $result, array $options): bool
    {
        // Early return if no packages are outdated
        if (!$result->hasOutdatedPackages()) {
            $this->io->writeError('🚫 Skipped Teams report', true, IO\IOInterface::VERBOSE);

            return true;
        }

        // Resolve configuration options
        ['url' => $url, 'additionalData' => $additionalData] = $this->resolver->resolve($options);

        // Make PHPStan happy
        assert(is_string($url));
        assert(is_string($additionalData));

        // Create report
        $report = Entity\Report\TeamsReport::create($result, $additionalData);

        // Send report
        return $this->taskRunner->run(
            '📤 Sending report to Teams',
            function (TaskRunner\RunnerContext $context) use ($url, $report) {
                try {
                    $response = $this->client->post($url, [
                        RequestOptions::JSON => $report,
                    ]);
                    $context->successful = 202 === $response->getStatusCode();
                } catch (Exception\GuzzleException) {
                    $context->successful = false;
                }

                return $context->successful;
            },
        );
    }

    /**
     * @throws OptionsResolver\Exception\ExceptionInterface
     */
    public function validateOptions(array $options): void
    {
        $this->resolver->resolve($options);
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    private function createOptionsResolver(): OptionsResolver\OptionsResolver
    {
        $resolver = new OptionsResolver\OptionsResolver();

        $resolver->define('url')
            ->allowedTypes('string')
            ->required()
            ->normalize(
                static fn (OptionsResolver\OptionsResolver $resolver, string $url) => new Psr7\Uri($url),
            )
        ;

        $resolver->define('additionalData')
            ->allowedTypes('string')
            ->default('')
        ;

        return $resolver;
    }
}
