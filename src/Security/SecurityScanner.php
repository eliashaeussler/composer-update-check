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

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use RuntimeException;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * SecurityScanner.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class SecurityScanner
{
    public const API_ENDPOINT = 'https://packagist.org/api/security-advisories';

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client = null)
    {
        $this->requestFactory = new Psr17Factory();
        $this->client = $client ?? new Psr18Client();
    }

    /**
     * @param OutdatedPackage[] $packages
     */
    public function scan(array $packages): ScanResult
    {
        // Early return if no packages are requested to be scanned
        if ([] === $packages) {
            return new ScanResult([]);
        }

        // Parse package names
        $packagesToScan = [];
        foreach ($packages as $package) {
            $packagesToScan[] = $package->getName();
        }

        // Build API request
        $query = http_build_query(['packages' => $packagesToScan]);
        $requestUri = new Uri(self::API_ENDPOINT);
        $requestUri = $requestUri->withQuery($query);
        $request = $this->requestFactory->createRequest('GET', $requestUri)->withHeader('Accept', 'application/json');

        // Send API request and evaluate response
        try {
            $response = $this->client->sendRequest($request);
            $apiResult = $response->getBody()->__toString();

            return ScanResult::fromApiResult(json_decode($apiResult, true) ?: []);
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException('Error while scanning security vulnerabilities.', 1610706128, $e);
        }
    }
}
