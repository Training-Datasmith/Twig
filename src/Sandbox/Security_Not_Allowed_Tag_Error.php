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
namespace Twig\Sandbox;

/**
 * Exception thrown when a not allowed tag is used in a template.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
final class Security_Not_Allowed_Tag_Error extends Security_Error
{
    public function __construct(string $message, private readonly string $tag_name)
    {
        parent::__construct($message);
    }
    public function get_tag_name(): string
    {
        return $this->tag_name;
    }
}