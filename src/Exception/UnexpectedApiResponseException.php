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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\ComposerUpdateCheck\Exception;

use CuyZ\Valinor;

use function array_map;
use function implode;
use function sprintf;

/**
 * UnexpectedApiResponseException.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UnexpectedApiResponseException extends Exception
{
    public static function create(Valinor\Mapper\MappingError $error = null): self
    {
        $suffix = '.';

        if (null !== $error) {
            $suffix = ':'.PHP_EOL.self::formatError($error);
        }

        return new self(
            sprintf('Received invalid API response from Packagist API%s', $suffix),
            1678126738,
            $error,
        );
    }

    private static function formatError(Valinor\Mapper\MappingError $error): string
    {
        $messages = Valinor\Mapper\Tree\Message\Messages::flattenFromNode($error->node());

        return implode(
            PHP_EOL,
            array_map(
                static fn (Valinor\Mapper\Tree\Message\NodeMessage $message) => '  * '.$message->toString(),
                $messages->toArray(),
            ),
        );
    }
}
