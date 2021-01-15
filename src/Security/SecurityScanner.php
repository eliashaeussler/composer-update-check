<?php
declare(strict_types=1);
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
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * SecurityScanner
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class SecurityScanner
{
    public const API_ENDPOINT = 'https://packagist.org/api/security-advisories';

    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ?? new Client(['base_uri' => static::API_ENDPOINT]);
    }

    /**
     * @param OutdatedPackage[] $packages
     * @return ScanResult
     */
    public function scan(array $packages): ScanResult
    {
        // Parse package names
        $packagesToScan = [];
        foreach ($packages as $package) {
            $packagesToScan[] = $package->getName();
        }

        // Send API request and evaluate response
        try {
            $response = $this->client->request('GET', '', [
                RequestOptions::HEADERS => ['Accept' => 'application/json'],
                RequestOptions::QUERY => ['packages' => $packagesToScan],
            ]);
            $apiResult = $response->getBody()->getContents();
            return ScanResult::fromApiResult(json_decode($apiResult, true) ?: []);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Error while scanning security vulnerabilities.', 1610706128, $e);
        }
    }
}
