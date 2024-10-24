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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Exception;

use CuyZ\Valinor;
use EliasHaeussler\ComposerUpdateCheck as Src;
use PHPUnit\Framework;

use function implode;

/**
 * CommandParametersAreInvalidTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Exception\CommandParametersAreInvalid::class)]
final class CommandParametersAreInvalidTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function constructorCreatesExceptionForGivenErrors(): void
    {
        $error = $this->buildMappingError();
        $nameMapping = [
            'foo' => '--foo',
        ];

        $expected = implode(PHP_EOL, [
            'Some command parameters are invalid:',
            '  * --foo: Value null is not a valid string.',
        ]);

        $actual = new Src\Exception\CommandParametersAreInvalid($error, $nameMapping);

        self::assertSame($expected, $actual->getMessage());
        self::assertSame(1722684456, $actual->getCode());
    }

    private function buildMappingError(): Valinor\Mapper\MappingError
    {
        try {
            (new Valinor\MapperBuilder())
                ->mapper()
                ->map('array{foo: string}', ['foo' => null]);
        } catch (Valinor\Mapper\MappingError $error) {
            return $error;
        }

        self::fail('Unable to build mapping error.');
    }
}
