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
use EliasHaeussler\ComposerUpdateCheck\Security\ScanResult;
use EliasHaeussler\ComposerUpdateCheck\Security\SecurityScanner;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * SecurityScannerTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class SecurityScannerTest extends AbstractTestCase
{
    /**
     * @var SecurityScanner
     */
    protected $subject;

    /**
     * @var ClientInterface|ObjectProphecy
     */
    protected $clientProphecy;

    protected function setUp(): void
    {
        $this->clientProphecy = $this->prophesize(ClientInterface::class);
        $this->subject = new SecurityScanner($this->clientProphecy->reveal());
    }

    /**
     * @test
     */
    public function scanReturnsScanResult(): void
    {
        $packages = [
            new OutdatedPackage('foo', '1.0.0', '1.0.1'),
            new OutdatedPackage('baz', '2.0.0', '2.1.0'),
        ];
        $apiResult = [
            'advisories' => [
                'foo' => [
                    ['affectedVersions' => '>=1.0.0,<2.0.0'],
                ],
            ],
        ];

        $response = new Response(200, [], json_encode($apiResult));
        $this->clientProphecy->request('GET', '', [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::QUERY => ['packages' => ['foo', 'baz']],
        ])->willReturn($response)->shouldBeCalledOnce();
        $scanResult = $this->subject->scan($packages);

        static::assertInstanceOf(ScanResult::class, $scanResult);
        static::assertCount(1, $scanResult->getInsecurePackages());
        static::assertSame('foo', $scanResult->getInsecurePackages()[0]->getName());
        static::assertSame(['>=1.0.0,<2.0.0'], $scanResult->getInsecurePackages()[0]->getAffectedVersions());
    }

    /**
     * @test
     */
    public function scanThrowsExceptionIfRequestFails(): void
    {
        $this->clientProphecy->request('GET', '', [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::QUERY => ['packages' => []],
        ])->willThrow(RequestException::class)->shouldBeCalledOnce();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1610706128);

        $this->subject->scan([]);
    }
}
