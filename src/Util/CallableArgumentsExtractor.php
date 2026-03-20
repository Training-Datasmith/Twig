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

use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Variadic_Expression;
use Twig\Node\Node;
use Twig\Twig_Callable_Interface;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class Callable_Arguments_Extractor
{
    private readonly Reflection_Callable $rc;
    public function __construct(private readonly Node $node, private readonly Twig_Callable_Interface $twig_callable)
    {
        $this->rc = new Reflection_Callable($twig_callable);
    }
    /**
     * @return array<Node>
     */
    public function extract_arguments(Node $arguments): array
    {
        $extracted_arguments = [];
        $extracted_argument_name_map = [];
        $named = false;
        foreach ($arguments as $name => $node) {
            if (!\is_int($name)) {
                $named = true;
            } elseif ($named) {
                throw new Syntax_Error(\sprintf('Positional arguments cannot be used after named arguments for %s "%s".', $this->twig_callable->get_type(), $this->twig_callable->get_name()), $this->node->get_template_line(), $this->node->get_source_context());
            }
            $extracted_arguments[$normalized_name = $this->normalize_name($name)] = $node;
            $extracted_argument_name_map[$normalized_name] = $name;
        }
        if (!$named && !$this->twig_callable->is_variadic()) {
            $min = $this->twig_callable->get_minimal_number_of_required_arguments();
            if (\count($extracted_arguments) < $this->rc->get_reflector()->get_number_of_required_parameters() - $min) {
                $arg_name = $this->to_snake_case($this->rc->get_reflector()->get_parameters()[$min + \count($extracted_arguments)]->get_name());
                throw new Syntax_Error(\sprintf('Value for argument "%s" is required for %s "%s".', $arg_name, $this->twig_callable->get_type(), $this->twig_callable->get_name()), $this->node->get_template_line(), $this->node->get_source_context());
            }
            return $extracted_arguments;
        }
        if (!$callable = $this->twig_callable->get_callable()) {
            if ($named) {
                throw new Syntax_Error(\sprintf('Named arguments are not supported for %s "%s".', $this->twig_callable->get_type(), $this->twig_callable->get_name()));
            }
            throw new Syntax_Error(\sprintf('Arbitrary positional arguments are not supported for %s "%s".', $this->twig_callable->get_type(), $this->twig_callable->get_name()));
        }
        [$callable_parameters, $is_php_variadic] = $this->get_callable_parameters();
        $arguments = [];
        $callable_parameter_names = [];
        $missing_arguments = [];
        $optional_arguments = [];
        $pos = 0;
        foreach ($callable_parameters as $callable_parameter) {
            $callable_parameter_name = $callable_parameter->name;
            if (\PHP_VERSION_ID >= 80000 && 'range' === $callable) {
                if ('start' === $callable_parameter_name) {
                    $callable_parameter_name = 'low';
                } elseif ('end' === $callable_parameter_name) {
                    $callable_parameter_name = 'high';
                }
            }
            $callable_parameter_names[] = $callable_parameter_name;
            $normalized_callable_parameter_name = $this->normalize_name($callable_parameter_name);
            if (\array_key_exists($normalized_callable_parameter_name, $extracted_arguments)) {
                if (\array_key_exists($pos, $extracted_arguments)) {
                    throw new Syntax_Error(\sprintf('Argument "%s" is defined twice for %s "%s".', $callable_parameter_name, $this->twig_callable->get_type(), $this->twig_callable->get_name()), $this->node->get_template_line(), $this->node->get_source_context());
                }
                if (\count($missing_arguments)) {
                    throw new Syntax_Error(\sprintf('Argument "%s" could not be assigned for %s "%s(%s)" because it is mapped to an internal PHP function which cannot determine default value for optional argument%s "%s".', $callable_parameter_name, $this->twig_callable->get_type(), $this->twig_callable->get_name(), implode(', ', array_map($this->to_snake_case(...), $callable_parameter_names)), \count($missing_arguments) > 1 ? 's' : '', implode('", "', $missing_arguments)), $this->node->get_template_line(), $this->node->get_source_context());
                }
                $arguments = array_merge($arguments, $optional_arguments);
                $arguments[] = $extracted_arguments[$normalized_callable_parameter_name];
                unset($extracted_arguments[$normalized_callable_parameter_name]);
                $optional_arguments = [];
            } elseif (\array_key_exists($pos, $extracted_arguments)) {
                $arguments = array_merge($arguments, $optional_arguments);
                $arguments[] = $extracted_arguments[$pos];
                unset($extracted_arguments[$pos]);
                $optional_arguments = [];
                ++$pos;
            } elseif ($callable_parameter->is_default_value_available()) {
                $optional_arguments[] = new Constant_Expression($callable_parameter->get_default_value(), $this->node->get_template_line());
            } elseif ($callable_parameter->is_optional()) {
                if (!$extracted_arguments) {
                    break;
                }
                $missing_arguments[] = $callable_parameter_name;
            } else {
                throw new Syntax_Error(\sprintf('Value for argument "%s" is required for %s "%s".', $this->to_snake_case($callable_parameter_name), $this->twig_callable->get_type(), $this->twig_callable->get_name()), $this->node->get_template_line(), $this->node->get_source_context());
            }
        }
        if ($this->twig_callable->is_variadic()) {
            $arbitrary_arguments = $is_php_variadic ? new Variadic_Expression([], $this->node->get_template_line()) : new Array_Expression([], $this->node->get_template_line());
            foreach ($extracted_arguments as $key => $value) {
                if (\is_int($key)) {
                    $arbitrary_arguments->add_element($value);
                } else {
                    $original_key = $extracted_argument_name_map[$key];
                    if ($original_key !== $this->to_snake_case($original_key)) {
                        trigger_deprecation('twig/twig', '3.15', \sprintf('Using "snake_case" for variadic arguments is required for a smooth upgrade with Twig 4.0; rename "%s" to "%s" in "%s" at line %d.', $original_key, $this->to_snake_case($original_key), $this->node->get_source_context()->get_name(), $this->node->get_template_line()));
                    }
                    $arbitrary_arguments->add_element($value, new Constant_Expression($this->to_snake_case($original_key), $this->node->get_template_line()));
                    // I Twig 4.0, don't convert the key:
                    // $arbitraryArguments->addElement($value, new ConstantExpression($originalKey, $this->node->getTemplateLine()));
                }
                unset($extracted_arguments[$key]);
            }
            if ($arbitrary_arguments->count()) {
                $arguments = array_merge($arguments, $optional_arguments);
                $arguments[] = $arbitrary_arguments;
            }
        }
        if ($extracted_arguments) {
            $unknown_argument = null;
            foreach ($extracted_arguments as $extracted_argument) {
                $unknown_argument = $extracted_argument;
                break;
            }
            throw new Syntax_Error(\sprintf('Unknown argument%s "%s" for %s "%s(%s)".', \count($extracted_arguments) > 1 ? 's' : '', implode('", "', array_keys($extracted_arguments)), $this->twig_callable->get_type(), $this->twig_callable->get_name(), implode(', ', array_map($this->to_snake_case(...), $callable_parameter_names))), $unknown_argument ? $unknown_argument->get_template_line() : $this->node->get_template_line(), $unknown_argument ? $unknown_argument->get_source_context() : $this->node->get_source_context());
        }
        return $arguments;
    }
    private function normalize_name(string|int $name): string
    {
        if (\is_int($name)) {
            return (string) $name;
        }
        return strtolower(str_replace('_', '', $name));
    }
    private function to_snake_case(string $name): string
    {
        return strtolower((string) preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z0-9])([A-Z])/'], '\1_\2', $name));
    }
    private function get_callable_parameters(): array
    {
        $parameters = $this->rc->get_reflector()->get_parameters();
        if ($this->node->has_node('node')) {
            array_shift($parameters);
        }
        if ($this->twig_callable->needs_charset()) {
            array_shift($parameters);
        }
        if ($this->twig_callable->needs_environment()) {
            array_shift($parameters);
        }
        if ($this->twig_callable->needs_context()) {
            array_shift($parameters);
        }
        foreach ($this->twig_callable->get_arguments() as $argument) {
            array_shift($parameters);
        }
        $is_php_variadic = false;
        if ($this->twig_callable->is_variadic()) {
            $argument = end($parameters);
            $is_array = $argument && $argument->has_type() && $argument->get_type() instanceof \ReflectionNamedType && 'array' === $argument->get_type()->get_name();
            if ($is_array && $argument->is_default_value_available() && [] === $argument->get_default_value()) {
                array_pop($parameters);
            } elseif ($argument && $argument->is_variadic()) {
                array_pop($parameters);
                $is_php_variadic = true;
            } else {
                throw new Syntax_Error(\sprintf('The last parameter of "%s" for %s "%s" must be an array with default value, eg. "array $arg = []".', $this->rc->get_name(), $this->twig_callable->get_type(), $this->twig_callable->get_name()));
            }
        }
        return [$parameters, $is_php_variadic];
    }
}