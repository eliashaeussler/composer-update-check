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

use Composer\Factory;
use Composer\Util;
use DateTimeImmutable;
use DateTimeInterface;
use EliasHaeussler\ComposerUpdateCheck\Entity;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

use function count;
use function json_encode;
use function md5;
use function sprintf;

/**
 * GitLabFormatter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class GitLabFormatter implements Formatter
{
    public const FORMAT = 'gitlab';

    public function __construct(
        private ?Console\Style\SymfonyStyle $io = null,
    ) {}

    public function formatResult(Entity\Result\UpdateCheckResult $result): void
    {
        // Early return if IO is missing
        if (null === $this->io) {
            return;
        }

        $this->renderCodeQualityArtifact(
            $this->buildDescription($result),
            $this->calculateSeverity($result),
            $result,
        );
    }

    private function buildDescription(Entity\Result\UpdateCheckResult $result): string
    {
        $numberOfOutdatedPackages = count($result->getOutdatedPackages());
        $numberOfExcludedPackages = count($result->getExcludedPackages());

        if ($numberOfExcludedPackages > 0) {
            $additionalInformation = sprintf(
                ' (skipped %d package%s)',
                $numberOfExcludedPackages,
                1 !== $numberOfExcludedPackages ? 's' : '',
            );
        } else {
            $additionalInformation = '';
        }

        if (0 === $numberOfOutdatedPackages) {
            return sprintf('All packages are up to date%s.', $additionalInformation);
        }

        if (1 === $numberOfOutdatedPackages) {
            return sprintf('1 package is outdated%s.', $additionalInformation);
        }

        return sprintf('%d packages are outdated%s.', $numberOfOutdatedPackages, $additionalInformation);
    }

    private function calculateSeverity(Entity\Result\UpdateCheckResult $result): string
    {
        if ([] === $result->getOutdatedPackages()) {
            return 'info';
        }

        $securityAdvisories = $result->getSecurityAdvisories();

        if ([] === $securityAdvisories) {
            return 'minor';
        }

        $severityLevels = array_map(
            static fn (Entity\Security\SecurityAdvisory $securityAdvisory) => $securityAdvisory->getSeverity(),
            $securityAdvisories,
        );
        $highestSeverityLevel = Entity\Security\SeverityLevel::getHighestSeverityLevel(...$severityLevels);

        return match ($highestSeverityLevel) {
            Entity\Security\SeverityLevel::High => 'critical',
            default => 'major',
        };
    }

    private function renderCodeQualityArtifact(
        string $description,
        string $severity,
        Entity\Result\UpdateCheckResult $result,
    ): void {
        // Early return if output is quiet
        if (false !== $this->io?->isQuiet()) {
            return;
        }

        // Resolve path to composer.json file
        $composerFile = Filesystem\Path::makeRelative(
            (string) realpath(Factory::getComposerFile()),
            Util\Platform::getCwd(),
        );

        $flags = JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

        // Pretty-print JSON on verbose output
        if ($this->io->isVerbose()) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $this->io->writeln(
            json_encode(
                [
                    'description' => $description,
                    'check_name' => 'composer-update-check',
                    'fingerprint' => $this->calculateFingerprint($result),
                    'severity' => $severity,
                    'location' => [
                        'path' => $composerFile,
                        'lines' => [
                            'begin' => 1,
                        ],
                    ],
                ],
                $flags,
            ),
        );
    }

    private function calculateFingerprint(Entity\Result\UpdateCheckResult $result): string
    {
        $json = json_encode(
            [
                'outdatedPackages' => $result->getOutdatedPackages(),
                'excludedPackages' => $result->getExcludedPackages(),
                'reportDate' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            ],
            JSON_THROW_ON_ERROR,
        );

        return md5($json);
    }

    public function setIO(Console\Style\SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public static function getFormat(): string
    {
        return self::FORMAT;
    }
}
