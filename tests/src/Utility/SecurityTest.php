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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Utility;

use EliasHaeussler\ComposerUpdateCheck\Entity\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Result\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Tests\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Tests\TestApplicationTrait;
use EliasHaeussler\ComposerUpdateCheck\Utility\Security;
use PHPUnit\Framework\Attributes\Test;

/**
 * SecurityTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class SecurityTest extends AbstractTestCase
{
    use TestApplicationTrait;

    protected function setUp(): void
    {
        $this->goToTestDirectory();
    }

    #[Test]
    public function scanReturnsSecurityVulnerabilities(): void
    {
        $securePackage = new OutdatedPackage('symfony/console', '4.4.0', '4.4.18');
        $insecurePackage = new OutdatedPackage('symfony/http-kernel', 'v4.4.9', 'v4.4.21');

        $scan = Security::scan([$securePackage, $insecurePackage]);

        self::assertFalse($scan->isInsecure($securePackage));
        self::assertTrue($scan->isInsecure($insecurePackage));
    }

    #[Test]
    public function scanAndOverlayResultsAppliesInsecureFlagsToInsecureOutdatedPackages(): void
    {
        $securePackage = new OutdatedPackage('symfony/console', '4.4.0', '4.4.18');
        $insecurePackage = new OutdatedPackage('symfony/http-kernel', 'v4.4.9', 'v4.4.21');
        $result = new UpdateCheckResult([$securePackage, $insecurePackage]);

        Security::scanAndOverlayResult($result);

        self::assertFalse($securePackage->isInsecure());
        self::assertTrue($insecurePackage->isInsecure());
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();
        parent::tearDown();
    }
}