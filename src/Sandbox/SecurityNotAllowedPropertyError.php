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
 * Exception thrown when a not allowed class property is used in a template.
 *
 * @author Kit Burton-Senior <mail@kitbs.com>
 */
final class Security_Not_Allowed_Property_Error extends Security_Error
{
    public function __construct(string $message, private readonly string $class_name, private readonly string $property_name)
    {
        parent::__construct($message);
    }
    public function get_class_name(): string
    {
        return $this->class_name;
    }
    public function get_property_name(): string
    {
        return $this->property_name;
    }
}