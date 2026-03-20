<?php

declare (strict_types=1);
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Twig\Expression_Parser;

/**
 * Represents a precedence change.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Precedence_Change
{
    public function __construct(private readonly string $package, private readonly string $version, private readonly int $new_precedence)
    {
    }
    public function get_package(): string
    {
        return $this->package;
    }
    public function get_version(): string
    {
        return $this->version;
    }
    public function get_new_precedence(): int
    {
        return $this->new_precedence;
    }
}