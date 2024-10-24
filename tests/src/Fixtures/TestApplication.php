<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020-2024 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Fixtures;

use Symfony\Component\Filesystem;

use function basename;
use function chdir;
use function dirname;
use function getcwd;
use function sys_get_temp_dir;
use function tempnam;

/**
 * TestApplication.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class TestApplication
{
    private readonly Filesystem\Filesystem $filesystem;
    private readonly string $tempDir;

    /**
     * @var list<array{string, string}>
     */
    private array $additionalFiles = [];
    private bool $booted = false;
    private ?string $originalDir = null;

    private function __construct(
        private string $path,
    ) {
        $this->filesystem = new Filesystem\Filesystem();
        $this->tempDir = (string) tempnam(sys_get_temp_dir(), 'composer-update-check_test_');
    }

    public static function normal(): self
    {
        return new self(
            dirname(__DIR__, 2).'/build/test-application',
        );
    }

    public static function empty(): self
    {
        return new self(
            dirname(__DIR__, 2).'/build/test-application-empty',
        );
    }

    public static function erroneous(): self
    {
        return new self(
            dirname(__DIR__, 2).'/build/test-application-erroneous',
        );
    }

    public function boot(): self
    {
        // Early return if application is already booted
        if ($this->booted) {
            return $this;
        }

        $this->originalDir = (string) getcwd();

        // Prepare test application
        $this->filesystem->remove($this->tempDir);
        $this->filesystem->mirror($this->path, $this->tempDir);

        // Copy additional files
        foreach ($this->additionalFiles as [$source, $target]) {
            $this->filesystem->copy(
                $source,
                Filesystem\Path::join($this->tempDir, $target),
            );
        }

        // Switch directory
        chdir($this->tempDir);

        // Clean up Composer environment
        $this->filesystem->remove(getcwd().'/vendor');

        // Update boot state
        $this->booted = true;

        return $this;
    }

    public function shutdown(): void
    {
        if (null !== $this->originalDir) {
            chdir($this->originalDir);
        }

        $this->filesystem->remove($this->tempDir);
        $this->booted = false;
    }

    public function reboot(): self
    {
        $this->shutdown();
        $this->boot();

        return $this;
    }

    public function useNormal(): self
    {
        $this->path = self::normal()->path;
        $this->reboot();

        return $this;
    }

    public function useEmpty(): self
    {
        $this->path = self::empty()->path;
        $this->reboot();

        return $this;
    }

    public function useErroneous(): self
    {
        $this->path = self::erroneous()->path;
        $this->reboot();

        return $this;
    }

    public function withConfig(string $filename, string $as = null): self
    {
        $this->additionalFiles[] = [
            $filename,
            $as ?? basename($filename),
        ];

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function __destruct()
    {
        $this->shutdown();
    }
}
