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
 * Exception thrown when a not allowed class method is used in a template.
 *
 * @author Kit Burton-Senior <mail@kitbs.com>
 */
final class SecurityNotAllowedMethodError extends SecurityError
{
    public function __construct(string $message, private readonly string $className, private readonly string $methodName)
    {
        parent::__construct($message);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }
}
