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

namespace EliasHaeussler\ComposerUpdateCheck\Package;

use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * OutdatedPackage.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class OutdatedPackage implements Package
{
    private const PROVIDER_LINK_PATTERN = 'https://packagist.org/packages/%s#%s';

    private UriInterface $providerLink;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name,
        private readonly string $outdatedVersion,
        private readonly string $newVersion,
        private bool $insecure = false,
    ) {
        $this->providerLink = $this->generateProviderLink();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOutdatedVersion(): string
    {
        return $this->outdatedVersion;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }

    public function isInsecure(): bool
    {
        return $this->insecure;
    }

    public function setInsecure(bool $insecure): self
    {
        $this->insecure = $insecure;

        return $this;
    }

    public function getProviderLink(): UriInterface
    {
        return $this->providerLink;
    }

    public function setProviderLink(UriInterface $providerLink): self
    {
        $this->providerLink = $providerLink;

        return $this;
    }

    /**
     * @return array{
     *     name: non-empty-string,
     *     outdatedVersion: string,
     *     newVersion: string,
     *     insecure: bool,
     *     providerLink: string,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'outdatedVersion' => $this->outdatedVersion,
            'newVersion' => $this->newVersion,
            'insecure' => $this->insecure,
            'providerLink' => (string) $this->providerLink,
        ];
    }

    public function __toString(): string
    {
        return $this->name;
    }

    private function generateProviderLink(): UriInterface
    {
        $versionHash = explode(' ', $this->newVersion, 2)[0];
        $uri = sprintf(self::PROVIDER_LINK_PATTERN, $this->name, $versionHash);

        return new Uri($uri);
    }
}
