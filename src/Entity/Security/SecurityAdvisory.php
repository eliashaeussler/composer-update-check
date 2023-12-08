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

namespace EliasHaeussler\ComposerUpdateCheck\Entity\Security;

use DateTimeImmutable;
use JsonSerializable;
use Psr\Http\Message\UriInterface;

use function preg_quote;
use function preg_replace;
use function sprintf;

/**
 * SecurityAdvisory.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class SecurityAdvisory implements JsonSerializable
{
    public function __construct(
        private readonly string $packageName,
        private readonly string $advisoryId,
        private readonly string $affectedVersions,
        private readonly string $title,
        private readonly DateTimeImmutable $reportedAt,
        private readonly SeverityLevel $severity,
        private readonly ?string $cve = null,
        private readonly ?UriInterface $link = null,
    ) {}

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function getAdvisoryId(): string
    {
        return $this->advisoryId;
    }

    public function getAffectedVersions(): string
    {
        return $this->affectedVersions;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSanitizedTitle(): string
    {
        if (null === $this->cve) {
            return $this->title;
        }

        $sanitizedTitle = preg_replace(
            sprintf('/^%s:\s/', preg_quote($this->cve)),
            '',
            $this->title,
        );

        if (null === $sanitizedTitle) {
            return $this->title;
        }

        return $sanitizedTitle;
    }

    public function getReportedAt(): DateTimeImmutable
    {
        return $this->reportedAt;
    }

    public function getSeverity(): SeverityLevel
    {
        return $this->severity;
    }

    public function getCVE(): ?string
    {
        return $this->cve;
    }

    public function getLink(): ?UriInterface
    {
        return $this->link;
    }

    /**
     * @return array{
     *     packageName: string,
     *     advisoryId: string,
     *     affectedVersions: string,
     *     title: string,
     *     reportedAt: DateTimeImmutable,
     *     severity: string,
     *     cve: string|null,
     *     link: string|null,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'packageName' => $this->packageName,
            'advisoryId' => $this->advisoryId,
            'affectedVersions' => $this->affectedVersions,
            'title' => $this->title,
            'reportedAt' => $this->reportedAt,
            'severity' => $this->severity->value,
            'cve' => $this->cve,
            'link' => null !== $this->link ? (string) $this->link : null,
        ];
    }
}
