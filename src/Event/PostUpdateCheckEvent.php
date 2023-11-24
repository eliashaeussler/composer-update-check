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

namespace EliasHaeussler\ComposerUpdateCheck\Event;

use Composer\EventDispatcher\Event;
use EliasHaeussler\ComposerUpdateCheck\IO\OutputBehavior;
use EliasHaeussler\ComposerUpdateCheck\Options;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;

/**
 * PostUpdateCheckEvent.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @codeCoverageIgnore
 */
class PostUpdateCheckEvent extends Event
{
    public const NAME = 'post-update-check';

    /**
     * @var UpdateCheckResult
     */
    private $updateCheckResult;

    /**
     * @var OutputBehavior
     */
    private $behavior;

    /**
     * @var Options
     */
    private $options;

    /**
     * @param string[] $args
     * @param string[] $flags
     */
    public function __construct(
        UpdateCheckResult $updateCheckResult,
        OutputBehavior $behavior,
        Options $options,
        array $args = [],
        array $flags = [],
    ) {
        parent::__construct(self::NAME, $args, $flags);
        $this->updateCheckResult = $updateCheckResult;
        $this->behavior = $behavior;
        $this->options = $options;
    }

    public function getUpdateCheckResult(): UpdateCheckResult
    {
        return $this->updateCheckResult;
    }

    public function getBehavior(): OutputBehavior
    {
        return $this->behavior;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }
}
