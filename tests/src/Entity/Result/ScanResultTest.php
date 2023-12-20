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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Entity\Result;

use DateTimeImmutable;
use EliasHaeussler\ComposerUpdateCheck as Src;
use Generator;
use PHPUnit\Framework;

/**
 * ScanResultTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Entity\Result\ScanResult::class)]
final class ScanResultTest extends Framework\TestCase
{
    /**
     * @param list<Src\Entity\Security\SecurityAdvisory> $expected
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('getSecurityAdvisoriesForPackageReturnsSecurityAdvisoriesAffectingGivenPackageDataProvider')]
    public function getSecurityAdvisoriesForPackageReturnsSecurityAdvisoriesAffectingGivenPackage(
        Src\Entity\Package\OutdatedPackage $outdatedPackage,
        array $expected,
    ): void {
        $subject = new Src\Entity\Result\ScanResult([
            'foo/foo' => [
                new Src\Entity\Security\SecurityAdvisory(
                    'foo/foo',
                    'advisoryId',
                    '>=1.0.0,<1.5.0|2.0.0',
                    'title',
                    new DateTimeImmutable('2023-12-24 18:00:00'),
                    Src\Entity\Security\SeverityLevel::Medium,
                ),
            ],
            'baz/baz' => [
                new Src\Entity\Security\SecurityAdvisory(
                    'baz/baz',
                    'advisoryId',
                    '1.0.0-alpha-1',
                    'title',
                    new DateTimeImmutable('2023-12-24 18:00:00'),
                    Src\Entity\Security\SeverityLevel::Medium,
                ),
            ],
        ]);

        self::assertEquals($expected, $subject->getSecurityAdvisoriesForPackage($outdatedPackage));
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('isPackageInsecureReturnsTrueIfPackageHasSecurityAdvisoriesDataProvider')]
    public function isPackageInsecureReturnsTrueIfPackageHasSecurityAdvisories(
        Src\Entity\Package\OutdatedPackage $outdatedPackage,
        bool $expected,
    ): void {
        $subject = new Src\Entity\Result\ScanResult([
            'foo/foo' => [
                new Src\Entity\Security\SecurityAdvisory(
                    'foo/foo',
                    'advisoryId',
                    '>=1.0.0,<1.5.0|2.0.0',
                    'title',
                    new DateTimeImmutable('2023-12-24 18:00:00'),
                    Src\Entity\Security\SeverityLevel::Medium,
                ),
            ],
            'baz/baz' => [
                new Src\Entity\Security\SecurityAdvisory(
                    'baz/baz',
                    'advisoryId',
                    '1.0.0-alpha-1',
                    'title',
                    new DateTimeImmutable('2023-12-24 18:00:00'),
                    Src\Entity\Security\SeverityLevel::Medium,
                ),
            ],
        ]);

        self::assertSame($expected, $subject->isPackageInsecure($outdatedPackage));
    }

    /**
     * @return Generator<string, array{Src\Entity\Package\OutdatedPackage, list<Src\Entity\Security\SecurityAdvisory>}>
     */
    public static function getSecurityAdvisoriesForPackageReturnsSecurityAdvisoriesAffectingGivenPackageDataProvider(): Generator
    {
        $fooAdvisory = new Src\Entity\Security\SecurityAdvisory(
            'foo/foo',
            'advisoryId',
            '>=1.0.0,<1.5.0|2.0.0',
            'title',
            new DateTimeImmutable('2023-12-24 18:00:00'),
            Src\Entity\Security\SeverityLevel::Medium,
        );
        $bazAdvisory = new Src\Entity\Security\SecurityAdvisory(
            'baz/baz',
            'advisoryId',
            '1.0.0-alpha-1',
            'title',
            new DateTimeImmutable('2023-12-24 18:00:00'),
            Src\Entity\Security\SeverityLevel::Medium,
        );

        yield 'secure package without security advisories' => [
            new Src\Entity\Package\OutdatedPackage(
                'secure/package',
                new Src\Entity\Version('1.0.0'),
                new Src\Entity\Version('1.0.1'),
            ),
            [],
        ];
        yield 'insecure package with secure versions' => [
            new Src\Entity\Package\OutdatedPackage(
                'baz/baz',
                new Src\Entity\Version('1.0.0'),
                new Src\Entity\Version('1.0.1'),
            ),
            [],
        ];
        yield 'insecure package' => [
            new Src\Entity\Package\OutdatedPackage(
                'foo/foo',
                new Src\Entity\Version('1.3.0'),
                new Src\Entity\Version('1.5.0'),
            ),
            [$fooAdvisory],
        ];
        yield 'insecure package with special version' => [
            new Src\Entity\Package\OutdatedPackage(
                'baz/baz',
                new Src\Entity\Version('1.0.0-alpha-1'),
                new Src\Entity\Version('1.0.0'),
            ),
            [$bazAdvisory],
        ];
    }

    /**
     * @return Generator<string, array{Src\Entity\Package\OutdatedPackage, bool}>
     */
    public static function isPackageInsecureReturnsTrueIfPackageHasSecurityAdvisoriesDataProvider(): Generator
    {
        yield 'secure package without security advisories' => [
            new Src\Entity\Package\OutdatedPackage(
                'secure/package',
                new Src\Entity\Version('1.0.0'),
                new Src\Entity\Version('1.0.1'),
            ),
            false,
        ];
        yield 'insecure package with secure versions' => [
            new Src\Entity\Package\OutdatedPackage(
                'baz/baz',
                new Src\Entity\Version('1.0.0'),
                new Src\Entity\Version('1.0.1'),
            ),
            false,
        ];
        yield 'insecure package' => [
            new Src\Entity\Package\OutdatedPackage(
                'foo/foo',
                new Src\Entity\Version('1.3.0'),
                new Src\Entity\Version('1.5.0'),
            ),
            true,
        ];
        yield 'insecure package with special version' => [
            new Src\Entity\Package\OutdatedPackage(
                'baz/baz',
                new Src\Entity\Version('1.0.0-alpha-1'),
                new Src\Entity\Version('1.0.0'),
            ),
            true,
        ];
    }
}
