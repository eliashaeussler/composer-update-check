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

namespace EliasHaeussler\ComposerUpdateCheck\Entity\Report;

use EliasHaeussler\ComposerUpdateCheck\Entity;
use JsonSerializable;

use function count;
use function implode;
use function sprintf;
use function str_repeat;

/**
 * MattermostReport.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class MattermostReport implements JsonSerializable
{
    /**
     * @param list<Dto\MattermostAttachment> $attachments
     */
    private function __construct(
        public readonly string $channel,
        public readonly string $text,
        public readonly array $attachments,
        public readonly ?string $iconEmoji = null,
        public readonly ?string $username = null,
    ) {}

    public static function create(
        string $channel,
        ?string $username,
        Entity\Result\UpdateCheckResult $result,
        string $rootPackageName = null,
    ): self {
        return new self(
            $channel,
            self::createText($result, $rootPackageName),
            self::createAttachments($result),
            ':rotating_light:',
            $username,
        );
    }

    private static function createText(Entity\Result\UpdateCheckResult $result, string $rootPackageName = null): string
    {
        $numberOfOutdatedPackages = count($result->getOutdatedPackages());
        $numberOfInsecurePackages = count($result->getInsecureOutdatedPackages());
        $textParts = [];

        // Outdated packages header
        $textParts[] = sprintf(
            '#### %d outdated%s package%s',
            $numberOfOutdatedPackages,
            $numberOfInsecurePackages > 0 ? sprintf(' (%d insecure)', $numberOfInsecurePackages) : '',
            1 !== $numberOfOutdatedPackages ? 's' : '',
        );

        // Outdated packages table
        $textParts[] = self::renderOutdatedPackagesTable($result, $rootPackageName);

        // Security advisories header
        if ($result->hasInsecureOutdatedPackages()) {
            $textParts[] = '##### Security advisories';
        }

        return implode(PHP_EOL, $textParts);
    }

    /**
     * @return list<Dto\MattermostAttachment>
     */
    private static function createAttachments(Entity\Result\UpdateCheckResult $result): array
    {
        $attachments = [];

        foreach ($result->getSecurityAdvisories() as $securityAdvisory) {
            $attachments[] = new Dto\MattermostAttachment(
                self::getColorForSeverityLevel($securityAdvisory->getSeverity()),
                self::renderSecurityAdvisoryTable($securityAdvisory),
            );
        }

        return $attachments;
    }

    private static function renderOutdatedPackagesTable(
        Entity\Result\UpdateCheckResult $result,
        string $rootPackageName = null,
    ): string {
        $numberOfExcludedPackages = count($result->getExcludedPackages());
        $hasInsecureOutdatedPackages = $result->hasInsecureOutdatedPackages();
        $textParts = [];

        if (null !== $rootPackageName) {
            $textParts[] = sprintf('##### %s', $rootPackageName);
        }

        $headers = [
            'Package',
            'Current version',
            'New version',
        ];

        if ($hasInsecureOutdatedPackages) {
            $headers[] = 'Severity';
        }

        $textParts[] = '|'.implode(' | ', $headers).'|';
        $textParts[] = str_repeat('|:--- ', count($headers)).'|';

        foreach ($result->getOutdatedPackages() as $outdatedPackage) {
            $severityLevel = $outdatedPackage->getHighestSeverityLevel();

            $row = sprintf(
                '| [%s](%s) | %s | **%s** |',
                $outdatedPackage->getName(),
                $outdatedPackage->getProviderLink(),
                $outdatedPackage->getOutdatedVersion(),
                $outdatedPackage->getNewVersion(),
            );

            if (null !== $severityLevel) {
                $row .= sprintf(
                    ' %s `%s` |',
                    self::getEmojiForSeverityLevel($severityLevel),
                    $severityLevel->value,
                );
            } elseif ($hasInsecureOutdatedPackages) {
                $row .= ' |';
            }

            $textParts[] = $row;
        }

        if ($numberOfExcludedPackages > 0) {
            $textParts[] = sprintf(
                '_%d package%s excluded from update check._',
                $numberOfExcludedPackages,
                1 !== $numberOfExcludedPackages ? 's were' : ' was',
            );
        }

        return implode(PHP_EOL, $textParts);
    }

    private static function renderSecurityAdvisoryTable(Entity\Security\SecurityAdvisory $securityAdvisory): string
    {
        $textParts = [
            sprintf('###### %s', $securityAdvisory->getSanitizedTitle()),
            sprintf('* Package: `%s`', $securityAdvisory->getPackageName()),
            sprintf('* Reported at: `%s`', $securityAdvisory->getReportedAt()->format('Y-m-d')),
        ];

        if (null !== $securityAdvisory->getCVE()) {
            $textParts[] = sprintf('* CVE: `%s`', $securityAdvisory->getCVE());
        }

        if (null !== $securityAdvisory->getLink()) {
            $textParts[] = sprintf('[Read more](%s)', $securityAdvisory->getLink());
        }

        return implode(PHP_EOL, $textParts);
    }

    private static function getColorForSeverityLevel(Entity\Security\SeverityLevel $severityLevel): string
    {
        return match ($severityLevel) {
            Entity\Security\SeverityLevel::Low => '#EEEEEE',
            Entity\Security\SeverityLevel::Medium => '#FFD966',
            Entity\Security\SeverityLevel::High => '#F67C41',
            Entity\Security\SeverityLevel::Critical => '#EE0000',
        };
    }

    private static function getEmojiForSeverityLevel(Entity\Security\SeverityLevel $severityLevel): string
    {
        return match ($severityLevel) {
            Entity\Security\SeverityLevel::Low => ':white_circle:',
            Entity\Security\SeverityLevel::Medium => ':large_yellow_circle:',
            Entity\Security\SeverityLevel::High => ':large_orange_circle:',
            Entity\Security\SeverityLevel::Critical => ':red_circle:',
        };
    }

    /**
     * @return array{
     *     channel: string,
     *     text: string,
     *     attachments: list<Dto\MattermostAttachment>,
     *     username?: string,
     * }
     */
    public function jsonSerialize(): array
    {
        $json = [
            'channel' => $this->channel,
            'text' => $this->text,
            'attachments' => $this->attachments,
            'icon_emoji' => $this->iconEmoji,
        ];

        if (null !== $this->username) {
            $json['username'] = $this->username;
        }

        return $json;
    }
}
