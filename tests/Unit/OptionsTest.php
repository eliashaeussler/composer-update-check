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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit;

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

use EliasHaeussler\ComposerUpdateCheck\Options;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * OptionsTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class OptionsTest extends AbstractTestCase
{
    /**
     * @var Options
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new Options();
    }

    /**
     * @test
     */
    public function fromInputReadsInputOptionsCorrectly(): void
    {
        $definition = new InputDefinition([
            new InputOption('ignore-packages'),
            new InputOption('no-dev'),
            new InputOption('security-scan'),
        ]);
        $input = new ArrayInput([
            '--ignore-packages' => ['foo/baz'],
            '--no-dev' => true,
            '--security-scan' => true,
        ], $definition);

        $subject = Options::fromInput($input);

        static::assertSame(['foo/baz'], $subject->getIgnorePackages());
        static::assertFalse($subject->isIncludingDevPackages());
        static::assertTrue($subject->isPerformingSecurityScan());
    }

    /**
     * @test
     */
    public function getIgnorePackagesReturnsIgnoredPackages(): void
    {
        $this->subject->setIgnorePackages(['foo/*', 'baz/*']);

        static::assertSame(['foo/*', 'baz/*'], $this->subject->getIgnorePackages());
    }

    /**
     * @test
     */
    public function isIncludingDevPackagesReturnsCorrectStateOfNoDevOption(): void
    {
        static::assertTrue($this->subject->isIncludingDevPackages());

        $this->subject->setIncludeDevPackages(false);

        static::assertFalse($this->subject->isIncludingDevPackages());

        $this->subject->setIncludeDevPackages(true);

        static::assertTrue($this->subject->isIncludingDevPackages());
    }

    /**
     * @test
     */
    public function isPerformingSecurityScanReturnsCorrectStateOfSecurityScanOption(): void
    {
        static::assertFalse($this->subject->isPerformingSecurityScan());

        $this->subject->setPerformSecurityScan(true);

        static::assertTrue($this->subject->isPerformingSecurityScan());

        $this->subject->setPerformSecurityScan(false);

        static::assertFalse($this->subject->isPerformingSecurityScan());
    }
}
