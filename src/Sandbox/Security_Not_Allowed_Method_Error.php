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
 * Exception thrown when a not allowed class method is used in a template.
 *
 * @author Kit Burton-Senior <mail@kitbs.com>
 */
final class Security_Not_Allowed_Method_Error extends Security_Error
{
    public function __construct(string $message, private readonly string $class_name, private readonly string $method_name)
    {
        parent::__construct($message);
    }
    public function get_class_name(): string
    {
        return $this->class_name;
    }
    public function get_method_name(): string
    {
        return $this->method_name;
    }
}