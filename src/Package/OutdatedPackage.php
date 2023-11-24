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

use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * OutdatedPackage.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class OutdatedPackage
{
    private const PROVIDER_LINK_PATTERN = 'https://packagist.org/packages/%s#%s';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $outdatedVersion;

    /**
     * @var string
     */
    private $newVersion;

    /**
     * @var bool
     */
    private $insecure;

    /**
     * @var UriInterface
     */
    private $providerLink;

    public function __construct(string $name, string $outdatedVersion, string $newVersion, bool $insecure = false)
    {
        $this->name = $name;
        $this->outdatedVersion = $outdatedVersion;
        $this->newVersion = $newVersion;
        $this->insecure = $insecure;
        $this->providerLink = $this->generateProviderLink();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOutdatedVersion(): string
    {
        return $this->outdatedVersion;
    }

    public function setOutdatedVersion(string $outdatedVersion): self
    {
        $this->outdatedVersion = $outdatedVersion;

        return $this;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }

    public function setNewVersion(string $newVersion): self
    {
        $this->newVersion = $newVersion;

        return $this;
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

    public function setProviderLink(Uri $providerLink): self
    {
        $this->providerLink = $providerLink;

        return $this;
    }

    private function generateProviderLink(): UriInterface
    {
        $versionHash = explode(' ', $this->newVersion, 2)[0];
        $uri = sprintf(self::PROVIDER_LINK_PATTERN, $this->name, $versionHash);

        return new Uri($uri);
    }
}
