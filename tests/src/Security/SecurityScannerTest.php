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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Security;

use DateTimeImmutable;
use EliasHaeussler\ComposerUpdateCheck as Src;
use EliasHaeussler\ComposerUpdateCheck\Tests;
use GuzzleHttp\Exception;
use GuzzleHttp\Handler;
use GuzzleHttp\Psr7;
use PHPUnit\Framework;

/**
 * SecurityScannerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Security\SecurityScanner::class)]
final class SecurityScannerTest extends Framework\TestCase
{
    private Tests\Fixtures\TestApplication $testApplication;
    private Handler\MockHandler $mockHandler;
    private Src\Security\SecurityScanner $subject;

    protected function setUp(): void
    {
        $this->testApplication = Tests\Fixtures\TestApplication::normal()->boot();

        $container = Tests\Fixtures\ContainerFactory::make($this->testApplication);

        $this->mockHandler = $container->get(Handler\MockHandler::class);
        $this->subject = $container->get(Src\Security\SecurityScanner::class);
    }

    #[Framework\Attributes\Test]
    public function scanReturnsEmptyScanResultIfNoPackagesAreRequestedToBeScanned(): void
    {
        $actual = $this->subject->scan([]);

        self::assertSame([], $actual->getSecurityAdvisories());
    }

    #[Framework\Attributes\Test]
    public function scanThrowsExceptionIfRequestFails(): void
    {
        $exception = new Exception\TransferException();
        $outdatedPackage = new Src\Entity\Package\OutdatedPackage(
            'foo',
            new Src\Entity\Version('1.0.0'),
            new Src\Entity\Version('1.0.1'),
        );

        $this->mockHandler->append($exception);

        $this->expectExceptionObject(new Src\Exception\UnableToFetchSecurityAdvisories($exception));

        $this->subject->scan([$outdatedPackage]);
    }

    #[Framework\Attributes\Test]
    public function scanThrowsExceptionOnInvalidPackagistApiResponse(): void
    {
        $outdatedPackage = new Src\Entity\Package\OutdatedPackage(
            'symfony/http-kernel',
            new Src\Entity\Version('v5.4.19'),
            new Src\Entity\Version('v5.4.33'),
        );

        $this->mockHandler->append(
            Tests\Fixtures\ResponseFactory::json('invalid-packagist-response'),
        );

        $this->expectException(Src\Exception\PackagistResponseHasErrors::class);

        $this->subject->scan([$outdatedPackage]);
    }

    #[Framework\Attributes\Test]
    public function scanReturnsScanResult(): void
    {
        $packages = [
            new Src\Entity\Package\OutdatedPackage(
                'symfony/console',
                new Src\Entity\Version('v4.4.0'),
                new Src\Entity\Version('v4.4.18'),
            ),
            new Src\Entity\Package\OutdatedPackage(
                'symfony/http-kernel',
                new Src\Entity\Version('v5.4.19'),
                new Src\Entity\Version('v5.4.33'),
            ),
        ];

        $this->mockHandler->append(
            Tests\Fixtures\ResponseFactory::json('symfony-http-kernel'),
        );

        $expected = [
            'symfony/http-kernel' => [
                new Src\Entity\Security\SecurityAdvisory(
                    'symfony/http-kernel',
                    'PKSA-hr4y-jwk2-1yb9',
                    '<5.4.0|>=5.4.0,<5.4.20|>=6.0.0',
                    'CVE-2022-24894: Prevent storing cookie headers in HttpCache',
                    new DateTimeImmutable('2023-02-01 08:00:00'),
                    Src\Entity\Security\SeverityLevel::Medium,
                    'CVE-2022-24894',
                    new Psr7\Uri('https://symfony.com/cve-2022-24894'),
                ),
            ],
        ];

        $actual = $this->subject->scan($packages);

        self::assertEquals($expected, $actual->getSecurityAdvisories());
    }

    #[Framework\Attributes\Test]
    public function scanAndOverlayResultAppliesSecurityAdvisoriesToInsecureOutdatedPackages(): void
    {
        $securePackage = new Src\Entity\Package\OutdatedPackage(
            'symfony/console',
            new Src\Entity\Version('v4.4.0'),
            new Src\Entity\Version('v4.4.18'),
        );
        $insecurePackage = new Src\Entity\Package\OutdatedPackage(
            'symfony/http-kernel',
            new Src\Entity\Version('v5.4.19'),
            new Src\Entity\Version('v5.4.33'),
        );
        $result = new Src\Entity\Result\UpdateCheckResult([$securePackage, $insecurePackage]);

        $this->mockHandler->append(
            Tests\Fixtures\ResponseFactory::json('symfony-http-kernel'),
        );

        $this->subject->scanAndOverlayResult($result);

        self::assertFalse($securePackage->isInsecure());
        self::assertTrue($insecurePackage->isInsecure());
    }

    protected function tearDown(): void
    {
        $this->testApplication->shutdown();
    }
}
