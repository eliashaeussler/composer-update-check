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

use function array_fill;
use function count;
use function sprintf;

/**
 * TeamsReport.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TeamsReport implements JsonSerializable
{
    private const SECURITY_ADVISORIES_CONTAINER_ID = 'securityAdvisories';

    /**
     * @param list<Dto\TeamsAttachment> $attachments
     */
    private function __construct(
        public readonly string $type,
        public readonly array $attachments,
    ) {}

    public static function create(Entity\Result\UpdateCheckResult $result): self
    {
        return new self(
            'message',
            self::createAttachments($result),
        );
    }

    /**
     * @return list<Dto\TeamsAttachment>
     */
    private static function createAttachments(Entity\Result\UpdateCheckResult $result): array
    {
        $attachment = Dto\TeamsAttachment::adaptiveCard(
            self::createCardBody($result),
            self::createFallbackText($result),
            self::createCardActions($result),
        );

        return [$attachment];
    }

    /**
     * @return list<Dto\TeamsContent>
     */
    private static function createCardBody(Entity\Result\UpdateCheckResult $result): array
    {
        $rootPackageName = $result->getRootPackage()?->getName();
        $numberOfOutdatedPackages = 0;
        $numberOfInsecurePackages = 0;
        $highestSeverityLevels = [];

        // Count outdated and insecure packages
        foreach ($result->getOutdatedPackages() as $outdatedPackage) {
            ++$numberOfOutdatedPackages;

            if ($outdatedPackage->isInsecure()) {
                ++$numberOfInsecurePackages;
                $highestSeverityLevels[] = $outdatedPackage->getHighestSeverityLevel();
            }
        }

        // Add title
        $contents = [
            Dto\TeamsContent::textBlock(
                text: sprintf(
                    '%d package%s%s %s outdated',
                    $numberOfOutdatedPackages,
                    1 !== $numberOfOutdatedPackages ? 's' : '',
                    $numberOfInsecurePackages > 0 ? sprintf(' (%d insecure)', $numberOfInsecurePackages) : '',
                    1 !== $numberOfOutdatedPackages ? 'are' : 'is',
                ),
                wrap: true,
                size: 'Large',
                weight: 'Bolder',
            ),
        ];

        // Add summary
        if (null !== $rootPackageName) {
            $contents[] = Dto\TeamsContent::textBlock(
                text: sprintf(
                    'Project **%s** has **%d outdated package%s**.',
                    $rootPackageName,
                    $numberOfOutdatedPackages,
                    1 !== $numberOfOutdatedPackages ? 's' : '',
                ),
                wrap: true,
            );

            if ([] !== $highestSeverityLevels) {
                $highestSeverityLevel = Entity\Security\SeverityLevel::getHighestSeverityLevel(...$highestSeverityLevels);
                $contents[] = Dto\TeamsContent::textBlock(
                    text: sprintf(
                        '%s marked as **insecure** with a highest severity of **%s**.',
                        $numberOfInsecurePackages === $numberOfOutdatedPackages
                            ? ($numberOfInsecurePackages > 1 ? 'They are' : 'It is')
                            : sprintf(
                                '%d of them %s',
                                $numberOfInsecurePackages,
                                $numberOfInsecurePackages > 1 ? 'are' : 'is',
                            ),
                        $highestSeverityLevel->value,
                    ),
                    wrap: true,
                    spacing: 'None',
                );
            }
        }

        // Add table with outdated packages
        $contents[] = self::createTableWithOutdatedPackages($result);

        // Add container with security advisories
        if ($numberOfInsecurePackages > 0) {
            $contents[] = self::createSecurityAdvisoriesContainer($result);
        }

        return $contents;
    }

    private static function createTableWithOutdatedPackages(Entity\Result\UpdateCheckResult $result): Dto\TeamsContent
    {
        $hasInsecureOutdatedPackages = $result->hasInsecureOutdatedPackages();
        $rowCells = [];
        $rowHeaders = [
            'Package',
            'Current version',
            'New version',
        ];

        if ($hasInsecureOutdatedPackages) {
            $rowHeaders[] = 'Security advisory';
        }

        foreach ($rowHeaders as $rowHeader) {
            $rowCells[] = new Dto\TeamsTableCell(
                [
                    Dto\TeamsContent::textBlock(
                        text: $rowHeader,
                        wrap: true,
                    ),
                ],
            );
        }

        $columns = array_fill(0, count($rowHeaders), new Dto\TeamsTableColumn(1));
        $rows = [
            new Dto\TeamsTableRow(
                cells: $rowCells,
                spacing: 'None',
                horizontalCellContentAlignment: 'Left',
                verticalCellContentAlignment: 'Top',
            ),
        ];

        // Add table rows
        foreach ($result->getOutdatedPackages() as $outdatedPackage) {
            $cells = [
                new Dto\TeamsTableCell(
                    [
                        Dto\TeamsContent::textBlock(
                            text: sprintf('[%s](%s)', $outdatedPackage->getName(), $outdatedPackage->getProviderLink()),
                            wrap: true,
                        ),
                    ],
                ),
                new Dto\TeamsTableCell(
                    [
                        Dto\TeamsContent::textBlock(
                            text: $outdatedPackage->getOutdatedVersion()->toString(),
                            wrap: true,
                        ),
                    ],
                ),
                new Dto\TeamsTableCell(
                    [
                        Dto\TeamsContent::textBlock(
                            text: $outdatedPackage->getNewVersion()->toString(),
                            wrap: true,
                            weight: 'Bolder',
                        ),
                    ],
                ),
            ];

            if ($outdatedPackage->isInsecure()) {
                $severityLevel = $outdatedPackage->getHighestSeverityLevel();
                $cells[] = new Dto\TeamsTableCell(
                    [
                        Dto\TeamsContent::textBlock(
                            text: sprintf(
                                '%s %s',
                                self::getEmojiForSeverityLevel($severityLevel),
                                $severityLevel->value,
                            ),
                            wrap: true,
                        ),
                    ],
                );
            } elseif ($hasInsecureOutdatedPackages) {
                $cells[] = new Dto\TeamsTableCell(
                    [
                        Dto\TeamsContent::textBlock(''),
                    ],
                );
            }

            $rows[] = new Dto\TeamsTableRow($cells);
        }

        return Dto\TeamsContent::table(
            columns: $columns,
            rows: $rows,
            firstRowAsHeader: true,
            gridStyle: 'default',
        );
    }

    private static function createSecurityAdvisoriesContainer(Entity\Result\UpdateCheckResult $result): Dto\TeamsContent
    {
        $items = [
            Dto\TeamsContent::textBlock(
                text: 'Security advisories',
                wrap: true,
                size: 'Large',
                weight: 'Bolder',
            ),
        ];

        foreach ($result->getInsecureOutdatedPackages() as $insecurePackage) {
            foreach ($insecurePackage->getSecurityAdvisories() as $securityAdvisory) {
                $facts = [
                    new Dto\TeamsFact('Package', $securityAdvisory->getPackageName()),
                    new Dto\TeamsFact('Reported at', $securityAdvisory->getReportedAt()->format('Y-m-d')),
                ];

                if (null !== $securityAdvisory->getCVE()) {
                    $facts[] = new Dto\TeamsFact('CVE', $securityAdvisory->getCVE());
                }

                $containerItems = [
                    Dto\TeamsContent::textBlock(
                        text: sprintf(
                            '%s %s',
                            self::getEmojiForSeverityLevel($securityAdvisory->getSeverity()),
                            $securityAdvisory->getSanitizedTitle(),
                        ),
                        wrap: true,
                        weight: 'Bolder',
                    ),
                    Dto\TeamsContent::factSet(
                        facts: $facts,
                        spacing: 'Small',
                    ),
                ];

                if (null !== $securityAdvisory->getLink()) {
                    $containerItems[] = Dto\TeamsContent::textBlock(
                        text: sprintf('[Read more](%s)', $securityAdvisory->getLink()),
                        wrap: true,
                        weight: 'Small',
                    );
                }

                $items[] = Dto\TeamsContent::container($containerItems);
            }
        }

        return Dto\TeamsContent::container(
            items: $items,
            isVisible: false,
            id: self::SECURITY_ADVISORIES_CONTAINER_ID,
            spacing: 'Medium',
        );
    }

    private static function createFallbackText(Entity\Result\UpdateCheckResult $result): string
    {
        $rootPackageName = $result->getRootPackage()?->getName();
        $numberOfOutdatedPackages = 0;
        $numberOfInsecurePackages = 0;
        $highestSeverityLevels = [];
        $addition = '';

        // Count outdated and insecure packages
        foreach ($result->getOutdatedPackages() as $outdatedPackage) {
            ++$numberOfOutdatedPackages;

            if ($outdatedPackage->isInsecure()) {
                ++$numberOfInsecurePackages;
                $highestSeverityLevels[] = $outdatedPackage->getHighestSeverityLevel();
            }
        }

        if ([] !== $highestSeverityLevels) {
            $highestSeverityLevel = Entity\Security\SeverityLevel::getHighestSeverityLevel(...$highestSeverityLevels);
            $addition = sprintf(
                ' %s marked as insecure with a highest severity of "%s".',
                $numberOfInsecurePackages === $numberOfOutdatedPackages
                    ? ($numberOfInsecurePackages > 1 ? 'They are' : 'It is')
                    : sprintf(
                        '%d of them %s',
                        $numberOfInsecurePackages,
                        $numberOfInsecurePackages > 1 ? 'are' : 'is',
                    ),
                $highestSeverityLevel->value,
            );
        }

        if (null !== $rootPackageName) {
            return sprintf(
                'Project "%s" has %d outdated package%s.%s',
                $rootPackageName,
                $numberOfOutdatedPackages,
                1 !== $numberOfOutdatedPackages ? 's' : '',
                $addition,
            );
        }

        return sprintf(
            '%d package%s are currently outdated.%s',
            $numberOfOutdatedPackages,
            1 !== $numberOfOutdatedPackages ? 's' : '',
            $addition,
        );
    }

    /**
     * @return list<Dto\TeamsAction>
     */
    private static function createCardActions(Entity\Result\UpdateCheckResult $result): array
    {
        if (!$result->hasInsecureOutdatedPackages()) {
            return [];
        }

        $actionId = 'showSecurityAdvisories';

        return [
            Dto\TeamsAction::toggleVisibility(
                $actionId,
                'Show security advisories',
                [
                    self::SECURITY_ADVISORIES_CONTAINER_ID,
                    $actionId,
                ],
            ),
        ];
    }

    private static function getEmojiForSeverityLevel(Entity\Security\SeverityLevel $severityLevel): string
    {
        return match ($severityLevel) {
            Entity\Security\SeverityLevel::Low => '⚪',
            Entity\Security\SeverityLevel::Medium => '🟡',
            Entity\Security\SeverityLevel::High => '🟠',
            Entity\Security\SeverityLevel::Critical => '🔴',
        };
    }

    /**
     * @return array{
     *     type: string,
     *     attachments: list<Dto\TeamsAttachment>,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'attachments' => $this->attachments,
        ];
    }
}
