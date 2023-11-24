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

use Symfony\Component\Filesystem\Filesystem;

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

    /**
     * @var string|null
     */
    protected $temporaryDirectory;

    protected function goToTestDirectory(string $applicationPath = AbstractTestCase::TEST_APPLICATION_NORMAL): void
    {
        $this->initialDirectory = getcwd();
        $this->temporaryDirectory = tempnam(sys_get_temp_dir(), str_replace('\\', '', strtolower(static::class)));

        $applicationVariant = $this->getApplicationVariant($applicationPath);
        if (null !== $applicationVariant) {
            $applicationVariant = '/'.$applicationVariant;
        }

        $filesystem = new Filesystem();
        $filesystem->remove($this->temporaryDirectory);
        $filesystem->mirror(dirname(__DIR__, 2).'/'.$applicationPath.$applicationVariant, $this->temporaryDirectory);

        chdir($this->temporaryDirectory);
        $this->cleanUpComposerEnvironment();
    }

    protected function getApplicationVariant(string $applicationPath): ?string
    {
        switch ($applicationPath) {
            case AbstractTestCase::TEST_APPLICATION_NORMAL:
                return 'v'.PHP_MAJOR_VERSION;

            default:
                return null;
        }
    }

    protected function goBackToInitialDirectory(): void
    {
        if (is_string($this->initialDirectory)) {
            chdir($this->initialDirectory);
        }

        $this->cleanUpTemporaryDirectory();
    }

    protected function cleanUpTemporaryDirectory(): void
    {
        if (is_string($this->temporaryDirectory)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->temporaryDirectory);
        }
    }

    protected function cleanUpComposerEnvironment(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove(getcwd().'/vendor');
    }
}
