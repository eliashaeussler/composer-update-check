<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-check".
 *
 * Copyright (C) 2020 Elias Häußler <elias@haeussler.dev>
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

use EliasHaeussler\ComposerUpdateCheck\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\UpdateCheckResult;
use PHPUnit\Framework\TestCase;

/**
 * UpdateCheckResultTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UpdateCheckResultTest extends TestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfOutdatedPackagesAreInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1600276584);

        new UpdateCheckResult(['foo']);
    }

    /**
     * @test
     */
    public function getOutdatedPackagesReturnsListOfOutdatedPackages(): void
    {
        $outdatedPackage1 = new OutdatedPackage('foo', '1.0.0', '1.0.5');
        $outdatedPackage2 = new OutdatedPackage('baz', '2.0.1', '2.1.2');
        $subject = new UpdateCheckResult([$outdatedPackage1, $outdatedPackage2]);

        static::assertCount(2, $subject->getOutdatedPackages());
        static::assertSame([$outdatedPackage1, $outdatedPackage2], $subject->getOutdatedPackages());
    }

    /**
     * @test
     * @dataProvider fromCommandOutputReturnsInstanceWithListOfCorrectlyParsedOutdatedPackagesDataProvider
     * @param string $commandOutput
     * @param array $expected
     */
    public function fromCommandOutputReturnsInstanceWithListOfCorrectlyParsedOutdatedPackages(
        string $commandOutput,
        array $expected
    ): void
    {
        $subject = UpdateCheckResult::fromCommandOutput($commandOutput);
        $outdatedPackages = $subject->getOutdatedPackages();

        static::assertCount(count($expected), $outdatedPackages);

        reset($outdatedPackages);
        /** @var OutdatedPackage $expectedOutdatedPackage */
        foreach ($expected as $expectedOutdatedPackage) {
            static::assertSame($expectedOutdatedPackage->getName(), current($outdatedPackages)->getName());
            static::assertSame($expectedOutdatedPackage->getOutdatedVersion(), current($outdatedPackages)->getOutdatedVersion());
            static::assertSame($expectedOutdatedPackage->getNewVersion(), current($outdatedPackages)->getNewVersion());
            next($outdatedPackages);
        }
    }

    /**
     * @test
     * @dataProvider parseCommandOutputParsesCommandOutputCorrectlyDataProvider
     * @param string $commandOutput
     * @param OutdatedPackage|null $expected
     */
    public function parseCommandOutputParsesCommandOutputCorrectly(string $commandOutput, ?OutdatedPackage $expected): void
    {
        static::assertEquals($expected, UpdateCheckResult::parseCommandOutput($commandOutput));
    }

    public function fromCommandOutputReturnsInstanceWithListOfCorrectlyParsedOutdatedPackagesDataProvider(): array
    {
        return [
            'no output' => [
                '',
                [],
            ],
            'no outdated packages' => [
                'this is some dummy text' . PHP_EOL . 'Just ignore it.',
                [],
            ],
            'outdated packages' => [
                implode(PHP_EOL, [
                    'this is some dummy text',
                    'just ignore it',
                    'but these lines are important:',
                    '  - Updating foo/baz (1.0.0) to foo/baz (1.0.5)',
                    '  - Updating dummy/package (dev-master 12345) to dummy/package (dev-master 67890)',
                    'bye',
                ]),
                [
                    new OutdatedPackage('foo/baz', '1.0.0', '1.0.5'),
                    new OutdatedPackage('dummy/package', 'dev-master 12345', 'dev-master 67890'),
                ],
            ],
        ];
    }

    public function parseCommandOutputParsesCommandOutputCorrectlyDataProvider(): array
    {
        return [
            'no output' => [
                '',
                null,
            ],
            'no matching package' => [
                'this is just some dummy text',
                null,
            ],
            'matching package' => [
                '  - Updating foo/baz (1.0.0) to foo/baz (1.0.5)',
                new OutdatedPackage('foo/baz', '1.0.0', '1.0.5'),
            ],
        ];
    }
}
