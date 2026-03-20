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

use Twig\Markup;
use Twig\Template;
/**
 * Represents a security policy which need to be enforced when sandbox mode is enabled.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Security_Policy implements Security_Policy_Interface
{
    private array $allowed_methods;
    public function __construct(private array $allowed_tags = [], private array $allowed_filters = [], array $allowed_methods = [], private array $allowed_properties = [], private array $allowed_functions = [], private array $allowed_constants = [])
    {
        $this->set_allowed_methods($allowed_methods);
    }
    public function set_allowed_tags(array $tags): void
    {
        $this->allowed_tags = $tags;
    }
    public function set_allowed_filters(array $filters): void
    {
        $this->allowed_filters = $filters;
    }
    public function set_allowed_methods(array $methods): void
    {
        $this->allowed_methods = [];
        foreach ($methods as $class => $m) {
            $this->allowed_methods[$class] = array_map(strtolower(...), \is_array($m) ? $m : [$m]);
        }
    }
    public function set_allowed_properties(array $properties): void
    {
        $this->allowed_properties = $properties;
    }
    public function set_allowed_functions(array $functions): void
    {
        $this->allowed_functions = $functions;
    }
    public function set_allowed_constants(array $constants): void
    {
        $this->allowed_constants = $constants;
    }
    public function check_security($tags, $filters, $functions): void
    {
        foreach ($tags as $tag) {
            if (!\in_array($tag, $this->allowed_tags, true)) {
                if ('extends' === $tag) {
                    trigger_deprecation('twig/twig', '3.12', 'The "extends" tag is always allowed in sandboxes, but won\'t be in 4.0, please enable it explicitly in your sandbox policy if needed.');
                } elseif ('use' === $tag) {
                    trigger_deprecation('twig/twig', '3.12', 'The "use" tag is always allowed in sandboxes, but won\'t be in 4.0, please enable it explicitly in your sandbox policy if needed.');
                } else {
                    throw new Security_Not_Allowed_Tag_Error(\sprintf('Tag "%s" is not allowed.', $tag), $tag);
                }
            }
        }
        foreach ($filters as $filter) {
            if (!\in_array($filter, $this->allowed_filters, true)) {
                throw new Security_Not_Allowed_Filter_Error(\sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }
        foreach ($functions as $function) {
            if (!\in_array($function, $this->allowed_functions, true)) {
                throw new Security_Not_Allowed_Function_Error(\sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }
    }
    public function check_method_allowed($obj, $method): void
    {
        if ($obj instanceof Template || $obj instanceof Markup) {
            return;
        }
        $allowed = false;
        $method = strtolower($method);
        foreach ($this->allowed_methods as $class => $methods) {
            if ($obj instanceof $class && \in_array($method, $methods, true)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            $class = $obj::class;
            throw new Security_Not_Allowed_Method_Error(\sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $class), $class, $method);
        }
    }
    public function check_constant_allowed(string $constant): void
    {
        if (!\in_array($constant, $this->allowed_constants, true)) {
            throw new Security_Not_Allowed_Constant_Error(\sprintf('Constant "%s" is not allowed.', $constant), $constant);
        }
    }
    public function check_property_allowed($obj, $property): void
    {
        $allowed = false;
        foreach ($this->allowed_properties as $class => $properties) {
            if ($obj instanceof $class && \in_array($property, \is_array($properties) ? $properties : [$properties], true)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            $class = $obj::class;
            throw new Security_Not_Allowed_Property_Error(\sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, $class), $class, $property);
        }
    }
}