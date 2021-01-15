<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Security;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2021 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Security\InsecurePackage;
use EliasHaeussler\ComposerUpdateCheck\Security\ScanResult;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;

/**
 * ScanResultTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class ScanResultTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfGivenInsecurePackagesAreInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1610707087);

        new ScanResult(['foo' => 'baz']);
    }

    /**
     * @test
     * @dataProvider fromApiResultReturnsEmptyScanResultObjectIfNoSecurityAdvisoriesWereProvidedDataProvider
     * @param array $apiResult
     */
    public function fromApiResultReturnsEmptyScanResultObjectIfNoSecurityAdvisoriesWereProvided(array $apiResult): void
    {
        static::assertSame([], ScanResult::fromApiResult($apiResult)->getInsecurePackages());
    }

    /**
     * @test
     */
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

        static::assertCount(2, $insecurePackages);
        static::assertSame('foo', $insecurePackages[0]->getName());
        static::assertSame(['>=1.0.0,<1.1.0', '2.0.0'], $insecurePackages[0]->getAffectedVersions());
        static::assertSame('baz', $insecurePackages[1]->getName());
        static::assertSame(['1.0.0-alpha-1'], $insecurePackages[1]->getAffectedVersions());
    }

    /**
     * @test
     */
    public function getInsecurePackagesReturnsInsecurePackagesFromScanResult(): void
    {
        $insecurePackages = [
            new InsecurePackage('foo', ['1.0.0']),
            new InsecurePackage('baz', ['2.0.0']),
        ];
        $subject = new ScanResult($insecurePackages);

        static::assertCount(2, $subject->getInsecurePackages());
        static::assertSame($insecurePackages, $subject->getInsecurePackages());
    }

    /**
     * @test
     * @dataProvider isInsecureReturnsSecurityStateOfGivenPackageDataProvider
     * @param OutdatedPackage $outdatedPackage
     * @param bool $expected
     */
    public function isInsecureReturnsSecurityStateOfGivenPackage(OutdatedPackage $outdatedPackage, bool $expected): void
    {
        $insecurePackages = [
            new InsecurePackage('foo', ['>=1.0.0,<1.5.0', '2.0.0']),
            new InsecurePackage('baz', ['1.0.0-alpha-1']),
        ];
        $subject = new ScanResult($insecurePackages);

        static::assertSame($expected, $subject->isInsecure($outdatedPackage));
    }

    public function fromApiResultReturnsEmptyScanResultObjectIfNoSecurityAdvisoriesWereProvidedDataProvider(): array
    {
        return [
            'empty array' => [
                [],
            ],
            'array without advisories' => [
                ['foo' => 'baz'],
            ],
            'array with empty advisories' => [
                ['advisories' => []],
            ],
        ];
    }

    public function isInsecureReturnsSecurityStateOfGivenPackageDataProvider(): array
    {
        return [
            'secure package without any insecure versions' => [
                new OutdatedPackage('secure/package', '1.0.0', '1.0.1'),
                false,
            ],
            'secure package with insecure versions' => [
                new OutdatedPackage('baz', '1.0.0', '1.0.1'),
                false,
            ],
            'insecure package' => [
                new OutdatedPackage('foo', '1.3.0', '1.5.0'),
                true,
            ],
            'insecure package with special version' => [
                new OutdatedPackage('baz', '1.0.0-alpha-1', '1.0.0'),
                true,
            ],
        ];
    }
}
