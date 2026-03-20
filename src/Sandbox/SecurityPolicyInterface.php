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
 * Interface that all security policy classes must implements.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface Security_Policy_Interface
{
    /**
     * @param string[] $tags
     * @param string[] $filters
     * @param string[] $functions
     *
     * @throws SecurityError
     */
    public function check_security($tags, $filters, $functions): void;
    /**
     * @param object $obj
     * @param string $method
     *
     * @throws SecurityNotAllowedMethodError
     */
    public function check_method_allowed($obj, $method): void;
    /**
     * @param object $obj
     * @param string $property
     *
     * @throws SecurityNotAllowedPropertyError
     */
    public function check_property_allowed($obj, $property): void;
    /**
     * @param string $constant The fully-qualified constant name
     *
     * @throws SecurityNotAllowedConstantError
     */
    public function check_constant_allowed(string $constant): void;
}