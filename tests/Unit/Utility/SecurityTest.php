<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Utility;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020 Elias Häußler <elias@haeussler.dev>
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
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\TestApplicationTrait;
use EliasHaeussler\ComposerUpdateCheck\Utility\Security;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use SensioLabs\Security\Exception\RuntimeException;

/**
 * SecurityTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class SecurityTest extends AbstractTestCase
{
    use TestApplicationTrait;

    /**
     * @var string|false
     */
    protected $backedUpComposerEnvVariable;

    protected function setUp(): void
    {
        $this->goToTestDirectory();
        $this->backedUpComposerEnvVariable = getenv('COMPOSER');
    }

    /**
     * @test
     */
    public function scanThrowsExceptionIfComposerLockFileIsNotAvailable(): void
    {
        putenv('COMPOSER=/foo');

        $this->expectException(RuntimeException::class);

        Security::scan();
    }

    /**
     * @test
     */
    public function scanReturnsSecurityVulnerabilities(): void
    {
        $scan = Security::scan();
        static::assertArrayHasKey('phpunit/phpunit', $scan);
        static::assertSame('5.0.10', $scan['phpunit/phpunit']['version']);
    }

    /**
     * @test
     */
    public function scanAndOverlayResultsAppliesInsecureFlagsToInsecureOutdatedPackages(): void
    {
        $securePackage = new OutdatedPackage('composer/composer', '1.0.0', '1.3.3');
        $insecurePackage = new OutdatedPackage('phpunit/phpunit', '5.0.10', '5.3.0');
        $result = new UpdateCheckResult([$securePackage, $insecurePackage,]);

        Security::scanAndOverlayResult($result);

        static::assertFalse($securePackage->isInsecure());
        static::assertTrue($insecurePackage->isInsecure());
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();

        if ($this->backedUpComposerEnvVariable === false) {
            putenv('COMPOSER');
        } else {
            putenv('COMPOSER=' . $this->backedUpComposerEnvVariable);
        }

        parent::tearDown();
    }
}
