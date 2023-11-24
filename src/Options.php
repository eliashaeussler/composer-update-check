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

namespace EliasHaeussler\ComposerUpdateCheck;

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

use Symfony\Component\Console\Input\InputInterface;

/**
 * Options.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Options
{
    /**
     * @var string[]
     */
    private $ignorePackages = [];

    /**
     * @var bool
     */
    private $includeDevPackages = true;

    /**
     * @var bool
     */
    private $performSecurityScan = false;

    public static function fromInput(InputInterface $input): self
    {
        $instance = new self();

        if ($input->hasOption('ignore-packages')) {
            $instance->setIgnorePackages($input->getOption('ignore-packages'));
        }
        if ($input->hasOption('no-dev')) {
            $instance->setIncludeDevPackages(!$input->getOption('no-dev'));
        }
        if ($input->hasOption('security-scan')) {
            $instance->setPerformSecurityScan($input->getOption('security-scan'));
        }

        return $instance;
    }

    /**
     * @return string[]
     */
    public function getIgnorePackages(): array
    {
        return $this->ignorePackages;
    }

    /**
     * @param string[] $ignorePackages
     */
    public function setIgnorePackages(array $ignorePackages): self
    {
        $this->ignorePackages = $ignorePackages;

        return $this;
    }

    public function isIncludingDevPackages(): bool
    {
        return $this->includeDevPackages;
    }

    public function setIncludeDevPackages(bool $includeDevPackages): self
    {
        $this->includeDevPackages = $includeDevPackages;

        return $this;
    }

    public function isPerformingSecurityScan(): bool
    {
        return $this->performSecurityScan;
    }

    public function setPerformSecurityScan(bool $performSecurityScan): self
    {
        $this->performSecurityScan = $performSecurityScan;

        return $this;
    }
}
