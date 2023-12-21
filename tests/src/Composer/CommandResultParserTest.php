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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Composer;

use EliasHaeussler\ComposerUpdateCheck as Src;
use Generator;
use PHPUnit\Framework;

use function implode;

/**
 * CommandResultParserTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Composer\CommandResultParser::class)]
final class CommandResultParserTest extends Framework\TestCase
{
    private Src\Composer\CommandResultParser $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Composer\CommandResultParser();
    }

    #[Framework\Attributes\Test]
    public function parseExcludesNonAllowedPackagesFromResult(): void
    {
        $output = implode(PHP_EOL, [
            '- Upgrading dummy/package (dev-master 12345 => dev-master 67890)',
            '- Upgrading foo/baz (1.0.0 => 1.0.5)',
        ]);
        $packages = [
            new Src\Entity\Package\InstalledPackage('foo/baz'),
        ];

        $actual = $this->subject->parse($output, $packages);

        self::assertCount(1, $actual);
        self::assertSame('foo/baz', $actual[0]->getName());
        self::assertSame('1.0.0', $actual[0]->getOutdatedVersion()->toString());
        self::assertSame('1.0.5', $actual[0]->getNewVersion()->toString());
    }

    /**
     * @param list<Src\Entity\Package\OutdatedPackage> $expected
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('parseReturnsListOfCorrectlyParsedOutdatedPackagesDataProvider')]
    public function parseReturnsListOfCorrectlyParsedOutdatedPackages(
        string $commandOutput,
        array $expected,
    ): void {
        $actual = $this->subject->parse(
            $commandOutput,
            [
                new Src\Entity\Package\InstalledPackage('dummy/package'),
                new Src\Entity\Package\InstalledPackage('foo/baz'),
            ],
        );

        self::assertEquals($expected, $actual);
    }

    /**
     * @return Generator<string, array{string, array<Src\Entity\Package\OutdatedPackage>}>
     */
    public static function parseReturnsListOfCorrectlyParsedOutdatedPackagesDataProvider(): Generator
    {
        yield 'no output' => [
            '',
            [],
        ];
        yield 'no outdated packages' => [
            'this is some dummy text'.PHP_EOL.'Just ignore it.',
            [],
        ];
        yield 'outdated packages' => [
            implode(PHP_EOL, [
                'this is some dummy text',
                'just ignore it',
                'but these lines are important:',
                '- Upgrading dummy/package (dev-master 12345 => dev-master 67890)',
                '- Upgrading foo/baz (1.0.0 => 1.0.5)',
                'bye',
            ]),
            [
                new Src\Entity\Package\OutdatedPackage(
                    'dummy/package',
                    new Src\Entity\Version('dev-master 12345'),
                    new Src\Entity\Version('dev-master 67890'),
                ),
                new Src\Entity\Package\OutdatedPackage(
                    'foo/baz',
                    new Src\Entity\Version('1.0.0'),
                    new Src\Entity\Version('1.0.5'),
                ),
            ],
        ];
    }
}
