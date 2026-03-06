<?php

declare(strict_types=1);

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node;

/**
 * Represents a deprecation for a named node or attribute on a Node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class NameDeprecation
{
    public function __construct(private readonly string $package = '', private readonly string $version = '', private readonly string $newName = '')
    {
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getNewName(): string
    {
        return $this->newName;
    }
}
