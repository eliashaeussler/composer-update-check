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

namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Package;

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\ExpectedCommandOutputTrait;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * UpdateCheckResultTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UpdateCheckResultTest extends AbstractTestCase
{
    use ExpectedCommandOutputTrait;

    private const ALLOWED_PACKAGES = [
        'foo/baz',
    ];

    #[Test]
    public function constructorThrowsExceptionIfOutdatedPackagesAreInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1600276584);

        /* @noinspection PhpParamsInspection */
        /* @phpstan-ignore-next-line */
        new UpdateCheckResult(['foo']);
    }

    #[Test]
    public function getOutdatedPackagesReturnsListOfOutdatedPackages(): void
    {
        $outdatedPackage1 = new OutdatedPackage('foo', '1.0.0', '1.0.5');
        $outdatedPackage2 = new OutdatedPackage('baz', '2.0.1', '2.1.2');
        $subject = new UpdateCheckResult([$outdatedPackage1, $outdatedPackage2]);

        self::assertCount(2, $subject->getOutdatedPackages());
        self::assertSame([$outdatedPackage2, $outdatedPackage1], $subject->getOutdatedPackages());
    }

    /**
     * @param OutdatedPackage[] $expected
     */
    #[Test]
    #[DataProvider('fromCommandOutputReturnsInstanceWithListOfCorrectlyParsedOutdatedPackagesDataProvider')]
    public function fromCommandOutputReturnsInstanceWithListOfCorrectlyParsedOutdatedPackages(
        string $commandOutput,
        array $expected,
    ): void {
        $subject = UpdateCheckResult::fromCommandOutput($commandOutput, ['dummy/package', 'foo/baz']);
        $outdatedPackages = $subject->getOutdatedPackages();

        self::assertCount(count($expected), $outdatedPackages);

        reset($outdatedPackages);
        foreach ($expected as $expectedOutdatedPackage) {
            self::assertSame($expectedOutdatedPackage->getName(), current($outdatedPackages)->getName());
            self::assertSame($expectedOutdatedPackage->getOutdatedVersion(), current($outdatedPackages)->getOutdatedVersion());
            self::assertSame($expectedOutdatedPackage->getNewVersion(), current($outdatedPackages)->getNewVersion());
            next($outdatedPackages);
        }
    }

    #[Test]
    public function fromCommandOutputExcludesNonAllowedPackagesFromResult(): void
    {
        $output = implode(PHP_EOL, [
            self::getExpectedCommandOutput('dummy/package', 'dev-master 12345', 'dev-master 67890'),
            self::getExpectedCommandOutput('foo/baz', '1.0.0', '1.0.5'),
        ]);

        $subject = UpdateCheckResult::fromCommandOutput($output, self::ALLOWED_PACKAGES);
        $outdatedPackages = $subject->getOutdatedPackages();

        self::assertCount(1, $outdatedPackages);
        self::assertSame('foo/baz', reset($outdatedPackages)->getName());
        self::assertSame('1.0.0', reset($outdatedPackages)->getOutdatedVersion());
        self::assertSame('1.0.5', reset($outdatedPackages)->getNewVersion());
    }

    /**
     * @return \Generator<string, array{string, array<OutdatedPackage>}>
     */
    public static function fromCommandOutputReturnsInstanceWithListOfCorrectlyParsedOutdatedPackagesDataProvider(): Generator
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
                self::getExpectedCommandOutput('dummy/package', 'dev-master 12345', 'dev-master 67890'),
                self::getExpectedCommandOutput('foo/baz', '1.0.0', '1.0.5'),
                'bye',
            ]),
            [
                new OutdatedPackage('dummy/package', 'dev-master 12345', 'dev-master 67890'),
                new OutdatedPackage('foo/baz', '1.0.0', '1.0.5'),
            ],
        ];
    }

    /**
     * @return \Generator<string, array{string, OutdatedPackage|null}>
     */
    public function parseCommandOutputParsesCommandOutputCorrectlyDataProvider(): Generator
    {
        yield 'no output' => [
            '',
            null,
        ];
        yield 'no matching package' => [
            'this is just some dummy text',
            null,
        ];
        yield 'matching package' => [
            self::getExpectedCommandOutput('foo/baz', '1.0.0', '1.0.5'),
            new OutdatedPackage('foo/baz', '1.0.0', '1.0.5'),
        ];
    }
}
