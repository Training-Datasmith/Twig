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
namespace Twig\Cache;

/**
 * Implements a no-cache strategy.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Null_Cache implements Cache_Interface, Removable_Cache_Interface
{
    public function generate_key(string $name, string $class_name): string
    {
        return '';
    }
    public function write(string $key, string $content): void
    {
    }
    public function load(string $key): void
    {
    }
    public function get_timestamp(string $key): int
    {
        return 0;
    }
    public function remove(string $name, string $cls): void
    {
    }
}