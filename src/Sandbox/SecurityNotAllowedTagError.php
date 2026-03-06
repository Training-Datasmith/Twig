<?php

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
final class SecurityNotAllowedTagError extends SecurityError
{
    public function __construct(string $message, private readonly string $tagName)
    {
        parent::__construct($message);
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }
}
