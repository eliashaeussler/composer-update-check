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

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use EliasHaeussler\ComposerUpdateCheck\Entity\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Entity\Result\ScanResult;
use EliasHaeussler\ComposerUpdateCheck\Entity\Result\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Exception\PackagistResponseHasErrors;
use EliasHaeussler\ComposerUpdateCheck\Exception\UnableToFetchSecurityAdvisories;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Spatie\Packagist\PackagistClient;

/**
 * SecurityScanner.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class SecurityScanner
{
    private TreeMapper $mapper;

    public function __construct(
        private PackagistClient $client,
    ) {
        $this->mapper = $this->createMapper();
    }

    /**
     * @param list<OutdatedPackage> $packages
     *
     * @throws PackagistResponseHasErrors
     * @throws UnableToFetchSecurityAdvisories
     */
    public function scan(array $packages): ScanResult
    {
        $packagesToScan = [];

        // Early return if no packages are requested to be scanned
        if ([] === $packages) {
            return new ScanResult([]);
        }

        foreach ($packages as $package) {
            $packagesToScan[$package->getName()] = $package->getOutdatedVersion()->get();
        }

        try {
            $advisories = $this->client->getAdvisoriesAffectingVersions($packagesToScan);
            $source = Source::array(['securityAdvisories' => $advisories]);

            return $this->mapper->map(ScanResult::class, $source);
        } catch (GuzzleException $exception) {
            throw new UnableToFetchSecurityAdvisories($exception);
        } catch (MappingError $error) {
            throw new PackagistResponseHasErrors($error);
        }
    }

    /**
     * @throws PackagistResponseHasErrors
     * @throws UnableToFetchSecurityAdvisories
     */
    public function scanAndOverlayResult(UpdateCheckResult $result): void
    {
        $outdatedPackages = $result->getOutdatedPackages();
        $scanResult = $this->scan($outdatedPackages);

        foreach ($outdatedPackages as $outdatedPackage) {
            if ($scanResult->isInsecure($outdatedPackage)) {
                $outdatedPackage->setSecurityAdvisories(
                    $scanResult->getSecurityAdvisoriesForPackage($outdatedPackage),
                );
            }
        }
    }

    private function createMapper(): TreeMapper
    {
        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->infer(UriInterface::class, static fn () => Uri::class)
            ->supportDateFormats('Y-m-d H:i:s')
            ->mapper()
        ;
    }
}
