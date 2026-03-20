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
 * Exception thrown when a not allowed function is used in a template.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
final class Security_Not_Allowed_Function_Error extends Security_Error
{
    public function __construct(string $message, private readonly string $function_name)
    {
        parent::__construct($message);
    }
    public function get_function_name(): string
    {
        return $this->function_name;
    }
}