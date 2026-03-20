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
namespace Twig\Util;

use Twig\Twig_Callable_Interface;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class Reflection_Callable
{
    private \ReflectionMethod|\ReflectionFunction $reflector;
    private $callable;
    private $name;
    public function __construct(Twig_Callable_Interface $twig_callable)
    {
        $callable = $twig_callable->get_callable();
        if (\is_string($callable) && false !== $pos = strpos($callable, '::')) {
            $callable = [substr($callable, 0, $pos), substr($callable, 2 + $pos)];
        }
        if (\is_array($callable) && method_exists($callable[0], $callable[1])) {
            $this->reflector = $r = new \ReflectionMethod($callable[0], $callable[1]);
            $this->callable = $callable;
            $this->name = $r->class . '::' . $r->name;
            return;
        }
        $check_visibility = $callable instanceof \Closure;
        try {
            $closure = \Closure::from_callable($callable);
        } catch (\TypeError $e) {
            throw new \LogicException(\sprintf('Callback for %s "%s" is not callable in the current scope.', $twig_callable->get_type(), $twig_callable->get_name()), 0, $e);
        }
        $this->reflector = $r = new \ReflectionFunction($closure);
        if (str_contains($r->name, '{closure')) {
            $this->callable = $callable;
            $this->name = 'Closure';
            return;
        }
        if ($object = $r->get_closure_this()) {
            $callable = [$object, $r->name];
            $this->name = get_debug_type($object) . '::' . $r->name;
        } elseif (\PHP_VERSION_ID >= 80111 && $class = $r->get_closure_called_class()) {
            $callable = [$class->name, $r->name];
            $this->name = $class->name . '::' . $r->name;
        } elseif (\PHP_VERSION_ID < 80111 && $class = $r->get_closure_scope_class()) {
            $callable = [\is_array($callable) ? $callable[0] : $class->name, $r->name];
            $this->name = (\is_array($callable) ? $callable[0] : $class->name) . '::' . $r->name;
        } else {
            $callable = $this->name = $r->name;
        }
        if ($check_visibility && \is_array($callable) && method_exists(...$callable) && !(new \ReflectionMethod(...$callable))->is_public()) {
            $callable = $r->get_closure();
        }
        $this->callable = $callable;
    }
    public function get_reflector(): \Reflection_Function_Abstract
    {
        return $this->reflector;
    }
    /**
     * @return callable
     */
    public function get_callable()
    {
        return $this->callable;
    }
    public function get_name(): string
    {
        return $this->name;
    }
}