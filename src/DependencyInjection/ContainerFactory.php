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

namespace EliasHaeussler\ComposerUpdateCheck\DependencyInjection;

use EliasHaeussler\ComposerUpdateCheck\DependencyInjection\CompilerPass\ContainerBuilderDebugDumpPass;
use EliasHaeussler\ComposerUpdateCheck\Exception\ConfigFileIsNotSupported;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Path;

use function array_unique;
use function dirname;
use function file_exists;
use function sys_get_temp_dir;
use function uniqid;

/**
 * ContainerFactory.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ContainerFactory
{
    /**
     * @var list<string>
     */
    private readonly array $configs;

    /**
     * @param list<string> $configs
     */
    public function __construct(array $configs = [])
    {
        $defaultConfigurationFiles = $this->getDefaultConfigurationFiles();

        $this->configs = array_unique([
            ...$defaultConfigurationFiles,
            ...$configs,
        ]);
    }

    /**
     * @throws ConfigFileIsNotSupported
     */
    public function make(bool $debug = false): ContainerInterface
    {
        $container = new ContainerBuilder();

        foreach ($this->configs as $config) {
            $loader = $this->createLoader($config, $container);
            $loader->load($config);
        }

        if ($debug) {
            $containerXmlFilename = $this->buildContainerXmlFilename($container);
            $container->addCompilerPass(new ContainerBuilderDebugDumpPass($containerXmlFilename));
        }

        $container->compile(true);

        return $container;
    }

    /**
     * @throws ConfigFileIsNotSupported
     */
    private function createLoader(string $filename, ContainerBuilder $container): LoaderInterface
    {
        $locator = new FileLocator($filename);

        return match (Path::getExtension($filename, true)) {
            'php' => new PhpFileLoader($container, $locator),
            'yaml', 'yml' => new YamlFileLoader($container, $locator),
            'xml' => new XmlFileLoader($container, $locator),
            default => throw new ConfigFileIsNotSupported($filename),
        };
    }

    /**
     * @return list<non-empty-string>
     */
    private function getDefaultConfigurationFiles(): array
    {
        $configDir = dirname(__DIR__, 2).'/config';

        return [
            $configDir.'/services.php',
            $configDir.'/services.yaml',
        ];
    }

    private function buildContainerXmlFilename(ContainerBuilder $container): string
    {
        $tempDir = sys_get_temp_dir();

        do {
            $filename = Path::join($tempDir, uniqid('ComposerUpdateCheck_')).'.xml';
        } while (file_exists($filename));

        $container->setParameter('debug.container_xml_filename', $filename);

        return $filename;
    }
}
