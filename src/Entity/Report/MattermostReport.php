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

namespace EliasHaeussler\ComposerUpdateCheck\Entity\Report;

use EliasHaeussler\ComposerUpdateCheck\Entity\Result\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Entity\Security\SecurityAdvisory;
use JsonSerializable;

use function count;
use function implode;
use function sprintf;

/**
 * MattermostReport.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class MattermostReport implements JsonSerializable
{
    /**
     * @param list<Attachment\MattermostAttachment> $attachments
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
        UpdateCheckResult $result,
        string $rootPackageName = null,
    ): self {
        return new self(
            $channel,
            self::createText($result),
            self::createAttachments($result, $rootPackageName),
            $result->hasInsecureOutdatedPackages() ? ':warning:' : ':package:',
            $username,
        );
    }

    private static function createText(UpdateCheckResult $result): string
    {
        $numberOfOutdatedPackages = 0;
        $numberOfInsecurePackages = 0;

        // Count outdated and insecure packages
        foreach ($result->getOutdatedPackages() as $outdatedPackage) {
            ++$numberOfOutdatedPackages;

            if ($outdatedPackage->isInsecure()) {
                ++$numberOfInsecurePackages;
            }
        }

        return sprintf(
            '#### :rotating_light: %d outdated%s package%s',
            $numberOfOutdatedPackages,
            $numberOfInsecurePackages > 0 ? sprintf(' (%d insecure)', $numberOfInsecurePackages) : '',
            1 !== $numberOfOutdatedPackages ? 's' : '',
        );
    }

    /**
     * @return list<Attachment\MattermostAttachment>
     */
    private static function createAttachments(UpdateCheckResult $result, string $rootPackageName = null): array
    {
        $securityAdvisories = $result->getSecurityAdvisories();
        $attachments = [];

        // Outdated packages
        $attachments[] = new Attachment\MattermostAttachment(
            '#EE0000',
            self::renderOutdatedPackagesTable($result, $rootPackageName),
        );

        // Security advisories
        if ([] !== $securityAdvisories) {
            $attachments[] = new Attachment\MattermostAttachment(
                '#EE0000',
                self::renderSecurityAdvisoriesTable($securityAdvisories),
            );
        }

        return $attachments;
    }

    private static function renderOutdatedPackagesTable(UpdateCheckResult $result, string $rootPackageName = null): string
    {
        $numberOfExcludedPackages = count($result->getExcludedPackages());
        $textParts = [];

        if (null !== $rootPackageName) {
            $textParts[] = sprintf('##### %s', $rootPackageName);
        }

        $textParts[] = '| Package | Current version | New version |';
        $textParts[] = '|:------- |:--------------- |:----------- |';

        foreach ($result->getOutdatedPackages() as $outdatedPackage) {
            $textParts[] = sprintf(
                '| [%s](%s) | %s%s | **%s** |',
                $outdatedPackage->getName(),
                $outdatedPackage->getProviderLink(),
                $outdatedPackage->getOutdatedVersion(),
                $outdatedPackage->isInsecure() ? ' :warning:' : '',
                $outdatedPackage->getNewVersion(),
            );
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

    /**
     * @param list<SecurityAdvisory> $securityAdvisories
     */
    private static function renderSecurityAdvisoriesTable(array $securityAdvisories): string
    {
        $textParts = [
            '##### Security advisories',
        ];

        foreach ($securityAdvisories as $securityAdvisory) {
            $textParts[] = sprintf('###### %s', $securityAdvisory->getTitle());
            $textParts[] = sprintf('* Package: `%s`', $securityAdvisory->getPackageName());
            $textParts[] = sprintf('* Advisory ID: `%s`', $securityAdvisory->getAdvisoryId());
            $textParts[] = sprintf('* Reported at: `%s`', $securityAdvisory->getReportedAt()->format('Y-m-d H:i:s'));
            $textParts[] = sprintf('* Severity: `%s`', $securityAdvisory->getSeverity());

            if (null !== $securityAdvisory->getCVE()) {
                $textParts[] = sprintf('* CVE: `%s`', $securityAdvisory->getCVE());
            }

            if (null !== $securityAdvisory->getLink()) {
                $textParts[] = sprintf('[Read more](%s)', $securityAdvisory->getLink());
            }
        }

        return implode(PHP_EOL, $textParts);
    }

    /**
     * @return array{
     *     channel: string,
     *     text: string,
     *     attachments: list<Attachment\MattermostAttachment>,
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
