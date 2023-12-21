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

namespace EliasHaeussler\ComposerUpdateCheck\Security;

use CuyZ\Valinor;
use EliasHaeussler\ComposerUpdateCheck\Entity;
use EliasHaeussler\ComposerUpdateCheck\Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use Psr\Http\Message;
use Spatie\Packagist;

/**
 * SecurityScanner.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class SecurityScanner
{
    private readonly Valinor\Mapper\TreeMapper $mapper;

    public function __construct(
        private readonly Packagist\PackagistClient $client,
    ) {
        $this->mapper = $this->createMapper();
    }

    /**
     * @param list<Entity\Package\OutdatedPackage> $packages
     *
     * @throws Exception\PackagistResponseHasErrors
     * @throws Exception\UnableToFetchSecurityAdvisories
     */
    public function scan(array $packages): Entity\Result\ScanResult
    {
        $packagesToScan = [];

        // Early return if no packages are requested to be scanned
        if ([] === $packages) {
            return new Entity\Result\ScanResult([]);
        }

        foreach ($packages as $package) {
            $packagesToScan[$package->getName()] = $package->getOutdatedVersion()->toString();
        }

        try {
            $advisories = $this->client->getAdvisoriesAffectingVersions($packagesToScan);
            $source = Valinor\Mapper\Source\Source::array(['securityAdvisories' => $advisories]);

            return $this->mapper->map(Entity\Result\ScanResult::class, $source);
        } catch (GuzzleException $exception) {
            throw new Exception\UnableToFetchSecurityAdvisories($exception);
        } catch (Valinor\Mapper\MappingError $error) {
            throw new Exception\PackagistResponseHasErrors($error);
        }
    }

    /**
     * @throws Exception\PackagistResponseHasErrors
     * @throws Exception\UnableToFetchSecurityAdvisories
     */
    public function scanAndOverlayResult(Entity\Result\UpdateCheckResult $result): void
    {
        $outdatedPackages = $result->getOutdatedPackages();
        $scanResult = $this->scan($outdatedPackages);

        foreach ($outdatedPackages as $outdatedPackage) {
            $outdatedPackage->setSecurityAdvisories(
                $scanResult->getSecurityAdvisoriesForPackage($outdatedPackage),
            );
        }
    }

    private function createMapper(): Valinor\Mapper\TreeMapper
    {
        return (new Valinor\MapperBuilder())
            ->allowSuperfluousKeys()
            ->infer(Message\UriInterface::class, static fn () => Psr7\Uri::class)
            ->supportDateFormats('Y-m-d H:i:s')
            ->mapper()
        ;
    }
}
