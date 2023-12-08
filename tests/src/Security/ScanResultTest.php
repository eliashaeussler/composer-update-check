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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Security;

use EliasHaeussler\ComposerUpdateCheck\Entity\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Result\ScanResult;
use EliasHaeussler\ComposerUpdateCheck\Entity\Security\SecurityAdvisory;
use EliasHaeussler\ComposerUpdateCheck\Tests\AbstractTestCase;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * ScanResultTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ScanResultTest extends AbstractTestCase
{
    #[Test]
    public function constructorThrowsExceptionIfGivenInsecurePackagesAreInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1610707087);

        /* @noinspection PhpParamsInspection */
        /* @phpstan-ignore-next-line */
        new ScanResult(['foo' => 'baz']);
    }

    /**
     * @param array<string, mixed> $apiResult
     */
    #[Test]
    #[DataProvider('fromApiResultReturnsEmptyScanResultObjectIfNoSecurityAdvisoriesWereProvidedDataProvider')]
    public function fromApiResultReturnsEmptyScanResultObjectIfNoSecurityAdvisoriesWereProvided(array $apiResult): void
    {
        self::assertSame([], ScanResult::fromApiResult($apiResult)->getInsecurePackages());
    }

    #[Test]
    public function fromApiResultReturnsScanResultObjectWithInsecurePackages(): void
    {
        $apiResult = [
            'advisories' => [
                'foo' => [
                    [
                        'affectedVersions' => '>=1.0.0,<1.1.0',
                    ],
                    [
                        'affectedVersions' => '2.0.0',
                    ],
                ],
                'baz' => [
                    [
                        'affectedVersions' => '1.0.0-alpha-1',
                    ],
                ],
            ],
        ];

        $subject = ScanResult::fromApiResult($apiResult);
        $insecurePackages = $subject->getInsecurePackages();

        self::assertCount(2, $insecurePackages);
        self::assertSame('foo', $insecurePackages[0]->getName());
        self::assertSame(['>=1.0.0,<1.1.0', '2.0.0'], $insecurePackages[0]->getAffectedVersions());
        self::assertSame('baz', $insecurePackages[1]->getName());
        self::assertSame(['1.0.0-alpha-1'], $insecurePackages[1]->getAffectedVersions());
    }

    #[Test]
    public function getInsecurePackagesReturnsInsecurePackagesFromScanResult(): void
    {
        $insecurePackages = [
            new SecurityAdvisory('foo', ['1.0.0']),
            new SecurityAdvisory('baz', ['2.0.0']),
        ];
        $subject = new ScanResult($insecurePackages);

        self::assertCount(2, $subject->getSecurityAdvisories());
        self::assertSame($insecurePackages, $subject->getSecurityAdvisories());
    }

    #[Test]
    #[DataProvider('isInsecureReturnsSecurityStateOfGivenPackageDataProvider')]
    public function isInsecureReturnsSecurityStateOfGivenPackage(OutdatedPackage $outdatedPackage, bool $expected): void
    {
        $insecurePackages = [
            new SecurityAdvisory('foo', ['>=1.0.0,<1.5.0', '2.0.0']),
            new SecurityAdvisory('baz', ['1.0.0-alpha-1']),
        ];
        $subject = new ScanResult($insecurePackages);

        self::assertSame($expected, $subject->isInsecure($outdatedPackage));
    }

    /**
     * @return Generator<string, mixed>
     */
    public static function fromApiResultReturnsEmptyScanResultObjectIfNoSecurityAdvisoriesWereProvidedDataProvider(): Generator
    {
        yield 'empty array' => [[]];
        yield 'array without advisories' => [['foo' => 'baz']];
        yield 'array with empty advisories' => [['advisories' => []]];
    }

    /**
     * @return Generator<string, mixed>
     */
    public static function isInsecureReturnsSecurityStateOfGivenPackageDataProvider(): Generator
    {
        yield 'secure package without any insecure versions' => [
            new OutdatedPackage('secure/package', '1.0.0', '1.0.1'),
            false,
        ];
        yield 'secure package with insecure versions' => [
            new OutdatedPackage('baz', '1.0.0', '1.0.1'),
            false,
        ];
        yield 'insecure package' => [
            new OutdatedPackage('foo', '1.3.0', '1.5.0'),
            true,
        ];
        yield 'insecure package with special version' => [
            new OutdatedPackage('baz', '1.0.0-alpha-1', '1.0.0'),
            true,
        ];
    }
}
