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

namespace Twig\Sandbox;

/**
 * Exception thrown when a not allowed constant is used in a template.
 */
final class SecurityNotAllowedConstantError extends SecurityError
{
    public function __construct(string $message, private readonly string $constantName)
    {
        parent::__construct($message);
    }

    public function getConstantName(): string
    {
        return $this->constantName;
    }
}
