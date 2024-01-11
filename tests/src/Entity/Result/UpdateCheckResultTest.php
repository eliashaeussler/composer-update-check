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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Entity\Result;

use DateTimeImmutable;
use EliasHaeussler\ComposerUpdateCheck as Src;
use Generator;
use PHPUnit\Framework;

/**
 * UpdateCheckResultTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Entity\Result\UpdateCheckResult::class)]
final class UpdateCheckResultTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function constructorSortsOutdatedPackages(): void
    {
        $bazPackage = new Src\Entity\Package\OutdatedPackage(
            'baz/baz',
            new Src\Entity\Version('1.5.0'),
            new Src\Entity\Version('1.7.2'),
        );
        $fooPackage = new Src\Entity\Package\OutdatedPackage(
            'foo/foo',
            new Src\Entity\Version('1.0.0'),
            new Src\Entity\Version('1.0.5'),
        );

        $actual = new Src\Entity\Result\UpdateCheckResult([$fooPackage, $bazPackage]);

        self::assertSame([$bazPackage, $fooPackage], $actual->getOutdatedPackages());
    }

    #[Framework\Attributes\Test]
    public function constructorSortsExcludedPackages(): void
    {
        $bazPackage = new Src\Entity\Package\OutdatedPackage(
            'baz/baz',
            new Src\Entity\Version('1.5.0'),
            new Src\Entity\Version('1.7.2'),
        );
        $fooPackage = new Src\Entity\Package\OutdatedPackage(
            'foo/foo',
            new Src\Entity\Version('1.0.0'),
            new Src\Entity\Version('1.0.5'),
        );

        $actual = new Src\Entity\Result\UpdateCheckResult([], [$fooPackage, $bazPackage]);

        self::assertSame([$bazPackage, $fooPackage], $actual->getExcludedPackages());
    }

    #[Framework\Attributes\Test]
    public function getSecurityAdvisoriesReturnsAllSecurityAdvisoriesOfOutdatedPackages(): void
    {
        $bazAdvisory = new Src\Entity\Security\SecurityAdvisory(
            'baz/baz',
            'advisoryId',
            '>=1.0.0,<1.7.2',
            'title',
            new DateTimeImmutable('2023-12-24 18:00:00'),
            Src\Entity\Security\SeverityLevel::Medium,
        );
        $fooAdvisory = new Src\Entity\Security\SecurityAdvisory(
            'foo/foo',
            'advisoryId',
            '>=1.0.0,<1.5.0|2.0.0',
            'title',
            new DateTimeImmutable('2023-12-24 18:00:00'),
            Src\Entity\Security\SeverityLevel::Medium,
        );
        $subject = new Src\Entity\Result\UpdateCheckResult([
            new Src\Entity\Package\OutdatedPackage(
                'baz/baz',
                new Src\Entity\Version('1.5.0'),
                new Src\Entity\Version('1.7.2'),
                [$bazAdvisory],
            ),
            new Src\Entity\Package\OutdatedPackage(
                'foo/foo',
                new Src\Entity\Version('1.0.0'),
                new Src\Entity\Version('1.5.0'),
                [$fooAdvisory],
            ),
        ]);

        self::assertSame([$bazAdvisory, $fooAdvisory], $subject->getSecurityAdvisories());
    }

    #[Framework\Attributes\Test]
    public function getInsecureOutdatedPackagesReturnsAllOutdatedPackagesWithSecurityAdvisories(): void
    {
        $fooPackage = new Src\Entity\Package\OutdatedPackage(
            'foo/foo',
            new Src\Entity\Version('1.0.0'),
            new Src\Entity\Version('1.5.0'),
            [
                new Src\Entity\Security\SecurityAdvisory(
                    'foo/foo',
                    'advisoryId',
                    '>=1.0.0,<1.5.0|2.0.0',
                    'title',
                    new DateTimeImmutable('2023-12-24 18:00:00'),
                    Src\Entity\Security\SeverityLevel::Medium,
                ),
            ],
        );

        $subject = new Src\Entity\Result\UpdateCheckResult([
            new Src\Entity\Package\OutdatedPackage(
                'baz/baz',
                new Src\Entity\Version('1.5.0'),
                new Src\Entity\Version('1.7.2'),
            ),
            $fooPackage,
        ]);

        self::assertSame([$fooPackage], $subject->getInsecureOutdatedPackages());
    }

    /**
     * @param list<Src\Entity\Package\OutdatedPackage> $outdatedPackages
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('hasInsecureOutdatedPackagesReturnsTrueIfAnyOutdatedPackagesHasSecurityAdvisoriesDataProvider')]
    public function hasInsecureOutdatedPackagesReturnsTrueIfAnyOutdatedPackagesHasSecurityAdvisories(
        array $outdatedPackages,
        bool $expected,
    ): void {
        $subject = new Src\Entity\Result\UpdateCheckResult($outdatedPackages);

        self::assertSame($expected, $subject->hasInsecureOutdatedPackages());
    }

    /**
     * @return Generator<string, array{list<Src\Entity\Package\OutdatedPackage>, bool}>
     */
    public static function hasInsecureOutdatedPackagesReturnsTrueIfAnyOutdatedPackagesHasSecurityAdvisoriesDataProvider(): Generator
    {
        $bazPackage = new Src\Entity\Package\OutdatedPackage(
            'baz/baz',
            new Src\Entity\Version('1.5.0'),
            new Src\Entity\Version('1.7.2'),
        );
        $fooPackage = new Src\Entity\Package\OutdatedPackage(
            'foo/foo',
            new Src\Entity\Version('1.0.0'),
            new Src\Entity\Version('1.5.0'),
        );
        $fooAdvisory = new Src\Entity\Security\SecurityAdvisory(
            'foo/foo',
            'advisoryId',
            '>=1.0.0,<1.5.0|2.0.0',
            'title',
            new DateTimeImmutable('2023-12-24 18:00:00'),
            Src\Entity\Security\SeverityLevel::Medium,
        );

        yield 'packages without security advisories' => [
            [$bazPackage, $fooPackage],
            false,
        ];
        yield 'packages with security advisories' => [
            [
                $bazPackage,
                (clone $fooPackage)->setSecurityAdvisories([$fooAdvisory]),
            ],
            true,
        ];
    }
}
