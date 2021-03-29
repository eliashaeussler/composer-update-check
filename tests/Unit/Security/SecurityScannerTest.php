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
use Http\Client\Exception\TransferException;
use Http\Message\RequestMatcher\CallbackRequestMatcher;
use Http\Mock\Client;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * SecurityScannerTest.
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
     * @var Client
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = new Client();
        $this->subject = new SecurityScanner($this->client);
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
        $response->getBody()->rewind();
        $matcher = function (RequestInterface $request) {
            self::assertSame('GET', $request->getMethod());
            self::assertSame(['application/json'], $request->getHeaders()['Accept']);
            self::assertSame(http_build_query(['packages' => ['foo', 'baz']]), $request->getUri()->getQuery());

            return true;
        };
        $this->client->on(new CallbackRequestMatcher($matcher), $response);

        $scanResult = $this->subject->scan($packages);

        static::assertInstanceOf(ScanResult::class, $scanResult);
        static::assertCount(1, $scanResult->getInsecurePackages());
        static::assertSame('foo', $scanResult->getInsecurePackages()[0]->getName());
        static::assertSame(['>=1.0.0,<2.0.0'], $scanResult->getInsecurePackages()[0]->getAffectedVersions());
    }

    /**
     * @test
     */
    public function scanReturnsEmptyScanResultIfNoPackagesAreRequestedToBeScanned(): void
    {
        static::assertSame([], $this->subject->scan([])->getInsecurePackages());
    }

    /**
     * @test
     */
    public function scanThrowsExceptionIfRequestFails(): void
    {
        $this->client->addException(new TransferException());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1610706128);

        $this->subject->scan([new OutdatedPackage('foo', '1.0.0', '1.0.1')]);
    }
}
