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

use EliasHaeussler\ComposerUpdateCheck\Entity\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Result\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Entity\Security\SecurityAdvisory;
use JsonSerializable;

use function count;
use function implode;
use function sprintf;

/**
 * SlackReport.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class SlackReport implements JsonSerializable
{
    private const MAX_BLOCKS = 45;

    /**
     * @param list<Dto\SlackBlock> $blocks
     */
    private function __construct(
        public readonly array $blocks,
    ) {}

    public static function create(
        UpdateCheckResult $result,
        string $rootPackageName = null,
    ): self {
        $remainingBlocks = self::MAX_BLOCKS;
        $remainingPackages = count($result->getOutdatedPackages());

        // Create header block
        $blocks = [
            self::createHeaderBlock($result),
        ];

        // Create package name block
        if (null !== $rootPackageName) {
            $blocks[] = self::createPackageNameBlock($rootPackageName);
        }

        // Create outdated package blocks
        foreach ($result->getOutdatedPackages() as $outdatedPackage) {
            if (--$remainingBlocks > 0) {
                $blocks[] = self::createOutdatedPackageBlock($outdatedPackage);
            } else {
                // Slack allows only a limited number of blocks, therefore
                // we have to omit the remaining packages and show a message instead
                $blocks[] = self::createMoreBlock($remainingPackages);
            }

            --$remainingPackages;
        }

        // Create security advisories block
        if ($remainingBlocks > 4) {
            $remainingSecurityAdvisories = count($result->getSecurityAdvisories());
            $remainingBlocks -= 2;
            $blocks[] = Dto\SlackBlock::divider();
            $blocks[] = Dto\SlackBlock::header(
                Dto\SlackBlockElement::plainText('Security advisories'),
            );

            foreach ($result->getSecurityAdvisories() as $securityAdvisory) {
                if (--$remainingBlocks > 1) {
                    $blocks[] = self::createSecurityAdvisoryBlock($securityAdvisory);
                } else {
                    // Slack allows only a limited number of blocks, therefore
                    // we have to omit the remaining security advisories and show a message instead
                    $blocks[] = self::createMoreBlock($remainingSecurityAdvisories);
                }

                --$remainingSecurityAdvisories;
            }
        }

        // Create insecure packages block
        if ($result->hasInsecureOutdatedPackages()) {
            $blocks[] = Dto\SlackBlock::divider();
            $blocks[] = self::createInsecurePackagesBlock();
        }

        return new self($blocks);
    }

    private static function createHeaderBlock(UpdateCheckResult $result): Dto\SlackBlock
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

        return Dto\SlackBlock::header(
            Dto\SlackBlockElement::plainText(
                sprintf(
                    '%d outdated%s package%s',
                    $numberOfOutdatedPackages,
                    $numberOfInsecurePackages > 0 ? sprintf(' (%d insecure)', $numberOfInsecurePackages) : '',
                    1 !== $numberOfOutdatedPackages ? 's' : '',
                ),
            ),
        );
    }

    private static function createPackageNameBlock(string $rootPackageName): Dto\SlackBlock
    {
        return Dto\SlackBlock::section(
            Dto\SlackBlockElement::markdown(
                sprintf('Project: *%s*', $rootPackageName),
            ),
        );
    }

    private static function createOutdatedPackageBlock(OutdatedPackage $outdatedPackage): Dto\SlackBlock
    {
        return Dto\SlackBlock::section(
            fields: [
                Dto\SlackBlockElement::markdown(
                    sprintf(
                        '<%s|%s>',
                        $outdatedPackage->getProviderLink(),
                        $outdatedPackage->getName(),
                    ),
                ),
                Dto\SlackBlockElement::markdown(
                    sprintf(
                        "*Current version:* %s%s\n*New version:* %s",
                        $outdatedPackage->getOutdatedVersion()->get(),
                        $outdatedPackage->isInsecure() ? ' :warning:' : '',
                        $outdatedPackage->getNewVersion()->get(),
                    ),
                ),
            ],
        );
    }

    private static function createSecurityAdvisoryBlock(SecurityAdvisory $securityAdvisory): Dto\SlackBlock
    {
        $textParts = [
            sprintf('*%s*', $securityAdvisory->getTitle()),
            sprintf('• Package: `%s`', $securityAdvisory->getPackageName()),
            sprintf('• Advisory ID: `%s`', $securityAdvisory->getAdvisoryId()),
            sprintf(
                '• Reported at: `<!date^%d^{date} at {time}|%s>`',
                $securityAdvisory->getReportedAt()->getTimestamp(),
                $securityAdvisory->getReportedAt()->format('Y-m-d H:i:s'),
            ),
            sprintf('• Severity: `%s`', $securityAdvisory->getSeverity()),
        ];

        if (null !== $securityAdvisory->getCVE()) {
            $textParts[] = sprintf('• CVE: `%s`', $securityAdvisory->getCVE());
        }

        if (null !== $securityAdvisory->getLink()) {
            $textParts[] = sprintf('<%s|Read more>', $securityAdvisory->getLink());
        }

        return Dto\SlackBlock::section(
            Dto\SlackBlockElement::markdown(
                implode(PHP_EOL, $textParts),
            ),
        );
    }

    private static function createMoreBlock(int $remaining): Dto\SlackBlock
    {
        return Dto\SlackBlock::section(
            Dto\SlackBlockElement::markdown(
                sprintf('_... and %d more_', $remaining),
            ),
        );
    }

    private static function createInsecurePackagesBlock(): Dto\SlackBlock
    {
        return Dto\SlackBlock::context(
            elements: [
                Dto\SlackBlockElement::markdown(
                    'Package versions marked with :warning: are insecure.',
                ),
            ],
        );
    }

    /**
     * @return array{
     *     blocks: list<Dto\SlackBlock>,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'blocks' => $this->blocks,
        ];
    }
}
