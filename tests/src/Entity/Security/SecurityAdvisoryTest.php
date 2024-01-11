<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2024 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Entity\Security;

use DateTimeImmutable;
use EliasHaeussler\ComposerUpdateCheck as Src;
use GuzzleHttp\Psr7;
use PHPUnit\Framework;

use function json_encode;

/**
 * SecurityAdvisoryTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Entity\Security\SecurityAdvisory::class)]
final class SecurityAdvisoryTest extends Framework\TestCase
{
    private Src\Entity\Security\SecurityAdvisory $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Entity\Security\SecurityAdvisory(
            'symfony/http-kernel',
            'PKSA-hr4y-jwk2-1yb9',
            '<5.4.0|>=5.4.0,<5.4.20|>=6.0.0',
            'CVE-2022-24894: Prevent storing cookie headers in HttpCache',
            new DateTimeImmutable('2023-02-01 08:00:00'),
            Src\Entity\Security\SeverityLevel::Medium,
            'CVE-2022-24894',
            new Psr7\Uri('https://symfony.com/cve-2022-24894'),
        );
    }

    #[Framework\Attributes\Test]
    public function getSanitizedTitleReturnsTitleIfCVEIsNotDefined(): void
    {
        $subject = new Src\Entity\Security\SecurityAdvisory(
            'symfony/http-kernel',
            'PKSA-hr4y-jwk2-1yb9',
            '<5.4.0|>=5.4.0,<5.4.20|>=6.0.0',
            'CVE-2022-24894: Prevent storing cookie headers in HttpCache',
            new DateTimeImmutable('2023-02-01 08:00:00'),
            Src\Entity\Security\SeverityLevel::Medium,
        );

        self::assertSame(
            'CVE-2022-24894: Prevent storing cookie headers in HttpCache',
            $subject->getSanitizedTitle(),
        );
    }

    #[Framework\Attributes\Test]
    public function getSanitizedTitleReturnsTitleWithoutCVE(): void
    {
        self::assertSame('Prevent storing cookie headers in HttpCache', $this->subject->getSanitizedTitle());
    }

    #[Framework\Attributes\Test]
    public function subjectIsJsonSerializable(): void
    {
        $expected = [
            'packageName' => 'symfony/http-kernel',
            'advisoryId' => 'PKSA-hr4y-jwk2-1yb9',
            'affectedVersions' => '<5.4.0|>=5.4.0,<5.4.20|>=6.0.0',
            'title' => 'CVE-2022-24894: Prevent storing cookie headers in HttpCache',
            'reportedAt' => new DateTimeImmutable('2023-02-01 08:00:00'),
            'severity' => Src\Entity\Security\SeverityLevel::Medium,
            'cve' => 'CVE-2022-24894',
            'link' => new Psr7\Uri('https://symfony.com/cve-2022-24894'),
        ];

        self::assertJsonStringEqualsJsonString(
            json_encode($expected, JSON_THROW_ON_ERROR),
            json_encode($this->subject, JSON_THROW_ON_ERROR),
        );
    }
}
