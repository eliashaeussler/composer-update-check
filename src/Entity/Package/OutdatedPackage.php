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

namespace EliasHaeussler\ComposerUpdateCheck\Entity\Package;

use EliasHaeussler\ComposerUpdateCheck\Entity\Security\SecurityAdvisory;
use EliasHaeussler\ComposerUpdateCheck\Entity\Security\SeverityLevel;
use EliasHaeussler\ComposerUpdateCheck\Entity\Version;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

use function usort;

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
     * @param non-empty-string       $name
     * @param list<SecurityAdvisory> $securityAdvisories
     */
    public function __construct(
        private readonly string $name,
        private readonly Version $outdatedVersion,
        private readonly Version $newVersion,
        private array $securityAdvisories = [],
    ) {
        $this->providerLink = $this->generateProviderLink();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOutdatedVersion(): Version
    {
        return $this->outdatedVersion;
    }

    public function getNewVersion(): Version
    {
        return $this->newVersion;
    }

    /**
     * @return list<SecurityAdvisory>
     */
    public function getSecurityAdvisories(): array
    {
        return $this->securityAdvisories;
    }

    public function getHighestSeverityLevel(): ?SeverityLevel
    {
        if ([] === $this->securityAdvisories) {
            return null;
        }

        $securityAdvisories = $this->securityAdvisories;

        usort(
            $securityAdvisories,
            static fn (SecurityAdvisory $a, SecurityAdvisory $b) => $a->getSeverity()->compareTo($b->getSeverity()),
        );

        return array_pop($securityAdvisories)->getSeverity();
    }

    /**
     * @param list<SecurityAdvisory> $securityAdvisories
     */
    public function setSecurityAdvisories(array $securityAdvisories): self
    {
        $this->securityAdvisories = $securityAdvisories;

        return $this;
    }

    public function isInsecure(): bool
    {
        return [] !== $this->securityAdvisories;
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
     *     securityAdvisories: list<SecurityAdvisory>,
     *     providerLink: string,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'outdatedVersion' => (string) $this->outdatedVersion,
            'newVersion' => (string) $this->newVersion,
            'securityAdvisories' => $this->securityAdvisories,
            'providerLink' => (string) $this->providerLink,
        ];
    }

    public function __toString(): string
    {
        return $this->name;
    }

    private function generateProviderLink(): UriInterface
    {
        $versionHash = explode(' ', $this->newVersion->get(), 2)[0];
        $uri = sprintf(self::PROVIDER_LINK_PATTERN, $this->name, $versionHash);

        return new Uri($uri);
    }
}
