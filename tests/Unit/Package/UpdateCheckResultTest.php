<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateCheck\Tests\Unit\Package;

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

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateCheck\Tests\Unit\ExpectedCommandOutputTrait;

/**
 * UpdateCheckResultTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UpdateCheckResultTest extends AbstractTestCase
{
    use ExpectedCommandOutputTrait;

    /**
     * @test
     */
    public function constructorThrowsExceptionIfOutdatedPackagesAreInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1600276584);

        /** @noinspection PhpParamsInspection */
        /** @phpstan-ignore-next-line */
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
        static::assertSame([$outdatedPackage2, $outdatedPackage1], $subject->getOutdatedPackages());
    }

    /**
     * @test
     * @dataProvider fromCommandOutputReturnsInstanceWithListOfCorrectlyParsedOutdatedPackagesDataProvider
     * @param string $commandOutput
     * @param OutdatedPackage[] $expected
     */
    public function fromCommandOutputReturnsInstanceWithListOfCorrectlyParsedOutdatedPackages(
        string $commandOutput,
        array $expected
    ): void {
        $subject = UpdateCheckResult::fromCommandOutput($commandOutput, ['dummy/package', 'foo/baz']);
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
     */
    public function fromCommandOutputExcludesNonAllowedPackagesFromResult(): void
    {
        $output = implode(PHP_EOL, [
            $this->getExpectedCommandOutput('dummy/package', 'dev-master 12345', 'dev-master 67890'),
            $this->getExpectedCommandOutput('foo/baz', '1.0.0', '1.0.5'),
        ]);
        $allowedPackages = [
            'foo/baz',
        ];

        $subject = UpdateCheckResult::fromCommandOutput($output, $allowedPackages);
        $outdatedPackages = $subject->getOutdatedPackages();

        static::assertCount(1, $outdatedPackages);
        static::assertSame('foo/baz', reset($outdatedPackages)->getName());
        static::assertSame('1.0.0', reset($outdatedPackages)->getOutdatedVersion());
        static::assertSame('1.0.5', reset($outdatedPackages)->getNewVersion());
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

    /**
     * @return array<string, array>
     */
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
                    $this->getExpectedCommandOutput('dummy/package', 'dev-master 12345', 'dev-master 67890'),
                    $this->getExpectedCommandOutput('foo/baz', '1.0.0', '1.0.5'),
                    'bye',
                ]),
                [
                    new OutdatedPackage('dummy/package', 'dev-master 12345', 'dev-master 67890'),
                    new OutdatedPackage('foo/baz', '1.0.0', '1.0.5'),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array>
     */
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
                $this->getExpectedCommandOutput('foo/baz', '1.0.0', '1.0.5'),
                new OutdatedPackage('foo/baz', '1.0.0', '1.0.5'),
            ],
        ];
    }
}
