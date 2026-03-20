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
namespace Twig\Extension;

use Twig\Attribute\As_Twig_Filter;
use Twig\Attribute\As_Twig_Function;
use Twig\Attribute\As_Twig_Test;
use Twig\Environment;
use Twig\Twig_Filter;
use Twig\Twig_Function;
use Twig\Twig_Test;
/**
 * Define Twig filters, functions, and tests with PHP attributes.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class Attribute_Extension extends Abstract_Extension
{
    private array $filters;
    private array $functions;
    private array $tests;
    /**
     * Use a runtime class using PHP attributes to define filters, functions, and tests.
     *
     * @param class-string $class
     */
    public function __construct(private readonly string $class)
    {
    }
    /**
     * @return class-string
     */
    public function get_class(): string
    {
        return $this->class;
    }
    public function get_filters(): array
    {
        if (!isset($this->filters)) {
            $this->init_from_attributes();
        }
        return $this->filters;
    }
    public function get_functions(): array
    {
        if (!isset($this->functions)) {
            $this->init_from_attributes();
        }
        return $this->functions;
    }
    public function get_tests(): array
    {
        if (!isset($this->tests)) {
            $this->init_from_attributes();
        }
        return $this->tests;
    }
    public function get_last_modified(): int
    {
        return max(filemtime(__FILE__), ($filename = (new \ReflectionClass($this->get_class()))->get_file_name()) && is_file($filename) ? filemtime($filename) : 0);
    }
    private function init_from_attributes(): void
    {
        $filters = $functions = $tests = [];
        $reflection_class = new \ReflectionClass($this->get_class());
        foreach ($reflection_class->get_methods() as $method) {
            foreach ($method->get_attributes(As_Twig_Filter::class) as $reflection_attribute) {
                /** @var AsTwigFilter $attribute */
                $attribute = $reflection_attribute->new_instance();
                $callable = new Twig_Filter($attribute->name, [$reflection_class->name, $method->get_name()], ['needs_context' => $attribute->needs_context ?? false, 'needs_environment' => $attribute->needs_environment ?? $this->needs_environment($method), 'needs_charset' => $attribute->needs_charset ?? false, 'is_variadic' => $method->is_variadic(), 'is_safe' => $attribute->is_safe, 'is_safe_callback' => $attribute->is_safe_callback, 'pre_escape' => $attribute->pre_escape, 'preserves_safety' => $attribute->preserves_safety, 'deprecation_info' => $attribute->deprecation_info]);
                if ($callable->get_minimal_number_of_required_arguments() > $method->get_number_of_parameters()) {
                    throw new \LogicException(\sprintf('"%s::%s()" needs at least %d arguments to be used AsTwigFilter, but only %d defined.', $reflection_class->get_name(), $method->get_name(), $callable->get_minimal_number_of_required_arguments(), $method->get_number_of_parameters()));
                }
                $filters[$attribute->name] = $callable;
            }
            foreach ($method->get_attributes(As_Twig_Function::class) as $reflection_attribute) {
                /** @var AsTwigFunction $attribute */
                $attribute = $reflection_attribute->new_instance();
                $callable = new Twig_Function($attribute->name, [$reflection_class->name, $method->get_name()], ['needs_context' => $attribute->needs_context ?? false, 'needs_environment' => $attribute->needs_environment ?? $this->needs_environment($method), 'needs_charset' => $attribute->needs_charset ?? false, 'is_variadic' => $method->is_variadic(), 'is_safe' => $attribute->is_safe, 'is_safe_callback' => $attribute->is_safe_callback, 'deprecation_info' => $attribute->deprecation_info]);
                if ($callable->get_minimal_number_of_required_arguments() > $method->get_number_of_parameters()) {
                    throw new \LogicException(\sprintf('"%s::%s()" needs at least %d arguments to be used AsTwigFunction, but only %d defined.', $reflection_class->get_name(), $method->get_name(), $callable->get_minimal_number_of_required_arguments(), $method->get_number_of_parameters()));
                }
                $functions[$attribute->name] = $callable;
            }
            foreach ($method->get_attributes(As_Twig_Test::class) as $reflection_attribute) {
                /** @var AsTwigTest $attribute */
                $attribute = $reflection_attribute->new_instance();
                $callable = new Twig_Test($attribute->name, [$reflection_class->name, $method->get_name()], ['needs_context' => $attribute->needs_context ?? false, 'needs_environment' => $attribute->needs_environment ?? $this->needs_environment($method), 'needs_charset' => $attribute->needs_charset ?? false, 'is_variadic' => $method->is_variadic(), 'deprecation_info' => $attribute->deprecation_info]);
                if ($callable->get_minimal_number_of_required_arguments() > $method->get_number_of_parameters()) {
                    throw new \LogicException(\sprintf('"%s::%s()" needs at least %d arguments to be used AsTwigTest, but only %d defined.', $reflection_class->get_name(), $method->get_name(), $callable->get_minimal_number_of_required_arguments(), $method->get_number_of_parameters()));
                }
                $tests[$attribute->name] = $callable;
            }
        }
        // Assign all at the end to avoid inconsistent state in case of exception
        $this->filters = array_values($filters);
        $this->functions = array_values($functions);
        $this->tests = array_values($tests);
    }
    /**
     * Detect if the first argument of the method is the environment.
     */
    private function needs_environment(\Reflection_Function_Abstract $function): bool
    {
        if (!$parameters = $function->get_parameters()) {
            return false;
        }
        return $parameters[0]->get_type() instanceof \ReflectionNamedType && Environment::class === $parameters[0]->get_type()->get_name() && !$parameters[0]->is_variadic();
    }
}