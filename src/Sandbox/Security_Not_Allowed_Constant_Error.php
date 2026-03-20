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
 * Exception thrown when a not allowed constant is used in a template.
 */
final class Security_Not_Allowed_Constant_Error extends Security_Error
{
    public function __construct(string $message, private readonly string $constant_name)
    {
        parent::__construct($message);
    }
    public function get_constant_name(): string
    {
        return $this->constant_name;
    }
}