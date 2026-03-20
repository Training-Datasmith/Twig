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
namespace Twig\Node\Expression;

use Twig\Compiler;
use Twig\Error\Syntax_Error;
use Twig\Extension\Extension_Interface;
use Twig\Node\Node;
use Twig\Twig_Callable_Interface;
use Twig\Twig_Filter;
use Twig\Twig_Function;
use Twig\Twig_Test;
use Twig\Util\Callable_Arguments_Extractor;
use Twig\Util\Reflection_Callable;
abstract class Call_Expression extends Abstract_Expression
{
    private ?\Twig\Util\Reflection_Callable $reflector = null;
    /**
     * @return void
     */
    protected function compile_callable(Compiler $compiler)
    {
        $twig_callable = $this->get_twig_callable();
        $callable = $twig_callable->get_callable();
        if (\is_string($callable) && !str_contains($callable, '::')) {
            $compiler->raw($callable);
        } else {
            $rc = $this->reflect_callable($twig_callable);
            $r = $rc->get_reflector();
            $callable = $rc->get_callable();
            if (\is_string($callable)) {
                $compiler->raw($callable);
            } elseif (\is_array($callable) && \is_string($callable[0])) {
                if (!$r instanceof \ReflectionMethod || $r->is_static()) {
                    $compiler->raw(\sprintf('%s::%s', $callable[0], $callable[1]));
                } else {
                    $compiler->raw(\sprintf('$this->env->getRuntime(\'%s\')->%s', $callable[0], $callable[1]));
                }
            } elseif (\is_array($callable) && $callable[0] instanceof Extension_Interface) {
                $class = $callable[0]::class;
                if (!$compiler->get_environment()->has_extension($class)) {
                    // Compile a non-optimized call to trigger a \Twig\Error\RuntimeError, which cannot be a compile-time error
                    $compiler->raw(\sprintf('$this->env->getExtension(\'%s\')', $class));
                } else {
                    $compiler->raw(\sprintf('$this->extensions[\'%s\']', ltrim($class, '\\')));
                }
                $compiler->raw(\sprintf('->%s', $callable[1]));
            } else {
                $compiler->raw(\sprintf('$this->env->get%s(\'%s\')->getCallable()', ucfirst((string) $this->get_attribute('type')), $twig_callable->get_dynamic_name()));
            }
        }
        $this->compile_arguments($compiler);
    }
    protected function compile_arguments(Compiler $compiler, $is_array = false): void
    {
        if (\func_num_args() >= 2) {
            trigger_deprecation('twig/twig', '3.11', 'Passing a second argument to "%s()" is deprecated.', __METHOD__);
        }
        $compiler->raw($is_array ? '[' : '(');
        $first = true;
        $twig_callable = $this->get_attribute('twig_callable');
        if ($twig_callable->needs_charset()) {
            $compiler->raw('$this->env->getCharset()');
            $first = false;
        }
        if ($twig_callable->needs_environment()) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->raw('$this->env');
            $first = false;
        }
        if ($twig_callable->needs_context()) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->raw('$context');
            $first = false;
        }
        foreach ($twig_callable->get_arguments() as $argument) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->string($argument);
            $first = false;
        }
        if ($this->has_node('node')) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->subcompile($this->get_node('node'));
            $first = false;
        }
        if ($this->has_node('arguments')) {
            $arguments = (new Callable_Arguments_Extractor($this, $this->get_twig_callable()))->extract_arguments($this->get_node('arguments'));
            foreach ($arguments as $node) {
                if (!$first) {
                    $compiler->raw(', ');
                }
                $compiler->subcompile($node);
                $first = false;
            }
        }
        $compiler->raw($is_array ? ']' : ')');
    }
    /**
     * @deprecated since Twig 3.12, use Twig\Util\CallableArgumentsExtractor::getArguments() instead
     */
    protected function get_arguments($callable, $arguments)
    {
        trigger_deprecation('twig/twig', '3.12', 'The "%s()" method is deprecated, use Twig\Util\CallableArgumentsExtractor::getArguments() instead.', __METHOD__);
        $call_type = $this->get_attribute('type');
        $call_name = $this->get_attribute('name');
        $parameters = [];
        $named = false;
        foreach ($arguments as $name => $node) {
            if (!\is_int($name)) {
                $named = true;
                $name = $this->normalize_name($name);
            } elseif ($named) {
                throw new Syntax_Error(\sprintf('Positional arguments cannot be used after named arguments for %s "%s".', $call_type, $call_name), $this->get_template_line(), $this->get_source_context());
            }
            $parameters[$name] = $node;
        }
        $is_variadic = $this->get_attribute('twig_callable')->is_variadic();
        if (!$named && !$is_variadic) {
            return $parameters;
        }
        if (!$callable) {
            if ($named) {
                $message = \sprintf('Named arguments are not supported for %s "%s".', $call_type, $call_name);
            } else {
                $message = \sprintf('Arbitrary positional arguments are not supported for %s "%s".', $call_type, $call_name);
            }
            throw new \LogicException($message);
        }
        [$callable_parameters, $is_php_variadic] = $this->get_callable_parameters($is_variadic);
        $arguments = [];
        $names = [];
        $missing_arguments = [];
        $optional_arguments = [];
        $pos = 0;
        foreach ($callable_parameters as $callable_parameter) {
            $name = $this->normalize_name($callable_parameter->name);
            if (\PHP_VERSION_ID >= 80000 && 'range' === $callable) {
                if ('start' === $name) {
                    $name = 'low';
                } elseif ('end' === $name) {
                    $name = 'high';
                }
            }
            $names[] = $name;
            if (\array_key_exists($name, $parameters)) {
                if (\array_key_exists($pos, $parameters)) {
                    throw new Syntax_Error(\sprintf('Argument "%s" is defined twice for %s "%s".', $name, $call_type, $call_name), $this->get_template_line(), $this->get_source_context());
                }
                if (\count($missing_arguments)) {
                    throw new Syntax_Error(\sprintf('Argument "%s" could not be assigned for %s "%s(%s)" because it is mapped to an internal PHP function which cannot determine default value for optional argument%s "%s".', $name, $call_type, $call_name, implode(', ', $names), \count($missing_arguments) > 1 ? 's' : '', implode('", "', $missing_arguments)), $this->get_template_line(), $this->get_source_context());
                }
                $arguments = array_merge($arguments, $optional_arguments);
                $arguments[] = $parameters[$name];
                unset($parameters[$name]);
                $optional_arguments = [];
            } elseif (\array_key_exists($pos, $parameters)) {
                $arguments = array_merge($arguments, $optional_arguments);
                $arguments[] = $parameters[$pos];
                unset($parameters[$pos]);
                $optional_arguments = [];
                ++$pos;
            } elseif ($callable_parameter->is_default_value_available()) {
                $optional_arguments[] = new Constant_Expression($callable_parameter->get_default_value(), -1);
            } elseif ($callable_parameter->is_optional()) {
                if (!$parameters) {
                    break;
                }
                $missing_arguments[] = $name;
            } else {
                throw new Syntax_Error(\sprintf('Value for argument "%s" is required for %s "%s".', $name, $call_type, $call_name), $this->get_template_line(), $this->get_source_context());
            }
        }
        if ($is_variadic) {
            $arbitrary_arguments = $is_php_variadic ? new Variadic_Expression([], -1) : new Array_Expression([], -1);
            foreach ($parameters as $key => $value) {
                if (\is_int($key)) {
                    $arbitrary_arguments->add_element($value);
                } else {
                    $arbitrary_arguments->add_element($value, new Constant_Expression($key, -1));
                }
                unset($parameters[$key]);
            }
            if ($arbitrary_arguments->count()) {
                $arguments = array_merge($arguments, $optional_arguments);
                $arguments[] = $arbitrary_arguments;
            }
        }
        if ($parameters) {
            $unknown_parameter = null;
            foreach ($parameters as $parameter) {
                if ($parameter instanceof Node) {
                    $unknown_parameter = $parameter;
                    break;
                }
            }
            throw new Syntax_Error(\sprintf('Unknown argument%s "%s" for %s "%s(%s)".', \count($parameters) > 1 ? 's' : '', implode('", "', array_keys($parameters)), $call_type, $call_name, implode(', ', $names)), $unknown_parameter ? $unknown_parameter->get_template_line() : $this->get_template_line(), $unknown_parameter ? $unknown_parameter->get_source_context() : $this->get_source_context());
        }
        return $arguments;
    }
    /**
     * @deprecated since Twig 3.12
     */
    protected function normalize_name(string $name): string
    {
        trigger_deprecation('twig/twig', '3.12', 'The "%s()" method is deprecated.', __METHOD__);
        return strtolower((string) preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\1_\2', '\1_\2'], $name));
    }
    // To be removed in 4.0
    private function get_callable_parameters(bool $is_variadic): array
    {
        $twig_callable = $this->get_attribute('twig_callable');
        $rc = $this->reflect_callable($twig_callable);
        $r = $rc->get_reflector();
        $callable_name = $rc->get_name();
        $parameters = $r->get_parameters();
        if ($this->has_node('node')) {
            array_shift($parameters);
        }
        if ($twig_callable->needs_charset()) {
            array_shift($parameters);
        }
        if ($twig_callable->needs_environment()) {
            array_shift($parameters);
        }
        if ($twig_callable->needs_context()) {
            array_shift($parameters);
        }
        foreach ($twig_callable->get_arguments() as $argument) {
            array_shift($parameters);
        }
        $is_php_variadic = false;
        if ($is_variadic) {
            $argument = end($parameters);
            $is_array = $argument && $argument->has_type() && $argument->get_type() instanceof \ReflectionNamedType && 'array' === $argument->get_type()->get_name();
            if ($is_array && $argument->is_default_value_available() && [] === $argument->get_default_value()) {
                array_pop($parameters);
            } elseif ($argument && $argument->is_variadic()) {
                array_pop($parameters);
                $is_php_variadic = true;
            } else {
                throw new \LogicException(\sprintf('The last parameter of "%s" for %s "%s" must be an array with default value, eg. "array $arg = []".', $callable_name, $this->get_attribute('type'), $twig_callable->get_name()));
            }
        }
        return [$parameters, $is_php_variadic];
    }
    private function reflect_callable(Twig_Callable_Interface $callable): Reflection_Callable
    {
        if (!$this->reflector) {
            $this->reflector = new Reflection_Callable($callable);
        }
        return $this->reflector;
    }
    /**
     * Overrides the Twig callable based on attributes (as potentially, attributes changed between the creation and the compilation of the node).
     *
     * To be removed in 4.0 and replace by $this->getAttribute('twig_callable').
     */
    private function get_twig_callable(): Twig_Callable_Interface
    {
        $current = $this->get_attribute('twig_callable');
        $this->set_attribute('twig_callable', match ($this->get_attribute('type')) {
            'test' => (new Twig_Test($this->get_attribute('name'), $this->has_attribute('callable') ? $this->get_attribute('callable') : $current->get_callable(), ['is_variadic' => $this->has_attribute('is_variadic') ? $this->get_attribute('is_variadic') : $current->is_variadic()]))->with_dynamic_arguments($this->get_attribute('name'), $this->has_attribute('dynamic_name') ? $this->get_attribute('dynamic_name') : $current->get_dynamic_name(), $this->has_attribute('arguments') ? $this->get_attribute('arguments') : $current->get_arguments()),
            'function' => (new Twig_Function($this->has_attribute('name') ? $this->get_attribute('name') : $current->get_name(), $this->has_attribute('callable') ? $this->get_attribute('callable') : $current->get_callable(), ['needs_environment' => $this->has_attribute('needs_environment') ? $this->get_attribute('needs_environment') : $current->needs_environment(), 'needs_context' => $this->has_attribute('needs_context') ? $this->get_attribute('needs_context') : $current->needs_context(), 'needs_charset' => $this->has_attribute('needs_charset') ? $this->get_attribute('needs_charset') : $current->needs_charset(), 'is_variadic' => $this->has_attribute('is_variadic') ? $this->get_attribute('is_variadic') : $current->is_variadic()]))->with_dynamic_arguments($this->get_attribute('name'), $this->has_attribute('dynamic_name') ? $this->get_attribute('dynamic_name') : $current->get_dynamic_name(), $this->has_attribute('arguments') ? $this->get_attribute('arguments') : $current->get_arguments()),
            'filter' => (new Twig_Filter($this->get_attribute('name'), $this->has_attribute('callable') ? $this->get_attribute('callable') : $current->get_callable(), ['needs_environment' => $this->has_attribute('needs_environment') ? $this->get_attribute('needs_environment') : $current->needs_environment(), 'needs_context' => $this->has_attribute('needs_context') ? $this->get_attribute('needs_context') : $current->needs_context(), 'needs_charset' => $this->has_attribute('needs_charset') ? $this->get_attribute('needs_charset') : $current->needs_charset(), 'is_variadic' => $this->has_attribute('is_variadic') ? $this->get_attribute('is_variadic') : $current->is_variadic()]))->with_dynamic_arguments($this->get_attribute('name'), $this->has_attribute('dynamic_name') ? $this->get_attribute('dynamic_name') : $current->get_dynamic_name(), $this->has_attribute('arguments') ? $this->get_attribute('arguments') : $current->get_arguments()),
        });
        return $this->get_attribute('twig_callable');
    }
}