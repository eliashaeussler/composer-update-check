<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit;

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

use Composer\Util\Filesystem;

/**
 * TestApplicationTrait.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
trait TestApplicationTrait
{
    /**
     * @var string|null
     */
    protected $initialDirectory;

    protected function goToTestDirectory(string $applicationPath = AbstractTestCase::TEST_APPLICATION_NORMAL): void
    {
        $this->goBackToInitialDirectory();
        $this->initialDirectory = getcwd();
        chdir($applicationPath);
        $this->cleanUpComposerEnvironment();
    }

    protected function goBackToInitialDirectory(): void
    {
        if (is_string($this->initialDirectory)) {
            chdir($this->initialDirectory);
        }
    }

    protected function cleanUpComposerEnvironment(): void
    {
        $filesystem = new Filesystem();
        $filesystem->removeDirectory(getcwd().'/vendor');
    }
}
