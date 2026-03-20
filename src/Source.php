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
namespace Twig;

/**
 * Holds information about a non-compiled Twig template.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Source
{
    /**
     * @param string $code The template source code
     * @param string $name The template logical name
     * @param string $path The filesystem path of the template if any
     */
    public function __construct(private readonly string $code, private readonly string $name, private readonly string $path = '')
    {
    }
    public function get_code(): string
    {
        return $this->code;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_path(): string
    {
        return $this->path;
    }
}