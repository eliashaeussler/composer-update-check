<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2024 Elias Häußler <elias@haeussler.dev>
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

use EliasHaeussler\ComposerUpdateCheck\Entity;
use GuzzleHttp\Psr7;
use Psr\Http\Message;

use function array_map;

/**
 * OutdatedPackage.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class OutdatedPackage implements Package
{
    private const PROVIDER_LINK_PATTERN = 'https://packagist.org/packages/%s#%s';

    private Message\UriInterface $providerLink;

    /**
     * @param non-empty-string                       $name
     * @param list<Entity\Security\SecurityAdvisory> $securityAdvisories
     */
    public function __construct(
        private readonly string $name,
        private readonly Entity\Version $outdatedVersion,
        private readonly Entity\Version $newVersion,
        private array $securityAdvisories = [],
    ) {
        $this->providerLink = $this->generateProviderLink();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOutdatedVersion(): Entity\Version
    {
        return $this->outdatedVersion;
    }

    public function getNewVersion(): Entity\Version
    {
        return $this->newVersion;
    }

    /**
     * @return list<Entity\Security\SecurityAdvisory>
     */
    public function getSecurityAdvisories(): array
    {
        return $this->securityAdvisories;
    }

    public function getHighestSeverityLevel(): ?Entity\Security\SeverityLevel
    {
        if ([] === $this->securityAdvisories) {
            return null;
        }

        $severityLevels = array_map(
            static fn (Entity\Security\SecurityAdvisory $securityAdvisory) => $securityAdvisory->getSeverity(),
            $this->securityAdvisories,
        );

        return Entity\Security\SeverityLevel::getHighestSeverityLevel(...$severityLevels);
    }

    /**
     * @param list<Entity\Security\SecurityAdvisory> $securityAdvisories
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

    public function getProviderLink(): Message\UriInterface
    {
        return $this->providerLink;
    }

    public function setProviderLink(Message\UriInterface $providerLink): self
    {
        $this->providerLink = $providerLink;

        return $this;
    }

    /**
     * @return array{
     *     name: non-empty-string,
     *     outdatedVersion: string,
     *     newVersion: string,
     *     securityAdvisories: list<Entity\Security\SecurityAdvisory>,
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

    private function generateProviderLink(): Message\UriInterface
    {
        $versionHash = explode(' ', $this->newVersion->toString(), 2)[0];
        $uri = sprintf(self::PROVIDER_LINK_PATTERN, $this->name, $versionHash);

        return new Psr7\Uri($uri);
    }
}
