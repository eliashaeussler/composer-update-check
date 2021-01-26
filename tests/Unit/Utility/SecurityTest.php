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

/**
 * SecurityTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class SecurityTest extends AbstractTestCase
{
    use TestApplicationTrait;

    protected function setUp(): void
    {
        $this->goToTestDirectory();
    }

    /**
     * @test
     */
    public function scanReturnsSecurityVulnerabilities(): void
    {
        $securePackage = new OutdatedPackage('symfony/console', '4.4.0', '4.4.18');
        $insecurePackage = new OutdatedPackage('phpunit/phpunit', '5.0.10', '5.3.0');

        $scan = Security::scan([$securePackage, $insecurePackage]);

        static::assertFalse($scan->isInsecure($securePackage));
        static::assertTrue($scan->isInsecure($insecurePackage));
    }

    /**
     * @test
     */
    public function scanAndOverlayResultsAppliesInsecureFlagsToInsecureOutdatedPackages(): void
    {
        $securePackage = new OutdatedPackage('symfony/console', '4.4.0', '4.4.18');
        $insecurePackage = new OutdatedPackage('phpunit/phpunit', '5.0.10', '5.3.0');
        $result = new UpdateCheckResult([$securePackage, $insecurePackage,]);

        Security::scanAndOverlayResult($result);

        static::assertFalse($securePackage->isInsecure());
        static::assertTrue($insecurePackage->isInsecure());
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();
        parent::tearDown();
    }
}
