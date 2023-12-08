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
use EliasHaeussler\ComposerUpdateCheck\Security\SecurityScanner;
use EliasHaeussler\ComposerUpdateCheck\Tests\AbstractTestCase;
use Http\Client\Exception\TransferException;
use Http\Message\RequestMatcher\CallbackRequestMatcher;
use Http\Mock\Client;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * SecurityScannerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class SecurityScannerTest extends AbstractTestCase
{
    private SecurityScanner $subject;

    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client();
        $this->subject = new SecurityScanner($this->client);
    }

    #[Test]
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

        self::assertInstanceOf(ScanResult::class, $scanResult);
        self::assertCount(1, $scanResult->getSecurityAdvisories());
        self::assertSame('foo', $scanResult->getSecurityAdvisories()[0]->getName());
        self::assertSame(['>=1.0.0,<2.0.0'], $scanResult->getSecurityAdvisories()[0]->getAffectedVersions());
    }

    #[Test]
    public function scanReturnsEmptyScanResultIfNoPackagesAreRequestedToBeScanned(): void
    {
        self::assertSame([], $this->subject->scan([])->getSecurityAdvisories());
    }

    #[Test]
    public function scanExcludesPackagesWithoutAffectedVersions(): void
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
                'baz' => [],
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

        self::assertInstanceOf(ScanResult::class, $scanResult);
        self::assertCount(1, $scanResult->getSecurityAdvisories());
        self::assertSame('foo', $scanResult->getSecurityAdvisories()[0]->getName());
        self::assertSame(['>=1.0.0,<2.0.0'], $scanResult->getSecurityAdvisories()[0]->getAffectedVersions());
    }

    #[Test]
    public function scanThrowsExceptionIfRequestFails(): void
    {
        $this->client->addException(new TransferException());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1610706128);

        $this->subject->scan([new OutdatedPackage('foo', '1.0.0', '1.0.1')]);
    }
}
