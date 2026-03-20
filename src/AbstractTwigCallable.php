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
namespace Twig;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Abstract_Twig_Callable implements Twig_Callable_Interface
{
    protected $options;
    private string $name;
    private ?string $dynamic_name = null;
    private array $arguments;
    public function __construct(string $name, private $callable = null, array $options = [])
    {
        $this->name = $this->dynamic_name = $name;
        $this->arguments = [];
        $this->options = array_merge(['needs_environment' => false, 'needs_context' => false, 'needs_charset' => false, 'is_variadic' => false, 'deprecation_info' => null, 'deprecated' => false, 'deprecating_package' => '', 'alternative' => null], $options);
        if ($this->options['deprecation_info'] && !$this->options['deprecation_info'] instanceof Deprecated_Callable_Info) {
            throw new \LogicException(\sprintf('The "deprecation_info" option must be an instance of "%s".', Deprecated_Callable_Info::class));
        }
        if ($this->options['deprecated']) {
            if ($this->options['deprecation_info']) {
                throw new \LogicException('When setting the "deprecation_info" option, you need to remove the obsolete deprecated options.');
            }
            trigger_deprecation('twig/twig', '3.15', 'Using the "deprecated", "deprecating_package", and "alternative" options is deprecated, pass a "deprecation_info" one instead.');
            $this->options['deprecation_info'] = new Deprecated_Callable_Info($this->options['deprecating_package'], $this->options['deprecated'], null, $this->options['alternative']);
        }
        if ($this->options['deprecation_info']) {
            $this->options['deprecation_info']->set_name($name);
            $this->options['deprecation_info']->set_type($this->get_type());
        }
    }
    public function __toString(): string
    {
        return \sprintf('%s(%s)', static::class, $this->name);
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_dynamic_name(): string
    {
        return $this->dynamic_name;
    }
    /**
     * @return callable|array{class-string, string}|null
     */
    public function get_callable()
    {
        return $this->callable;
    }
    public function get_node_class(): string
    {
        return $this->options['node_class'];
    }
    public function needs_charset(): bool
    {
        return $this->options['needs_charset'];
    }
    public function needs_environment(): bool
    {
        return $this->options['needs_environment'];
    }
    public function needs_context(): bool
    {
        return $this->options['needs_context'];
    }
    /**
     * @return static
     */
    public function with_dynamic_arguments(string $name, string $dynamic_name, array $arguments): self
    {
        $new = clone $this;
        $new->name = $name;
        $new->dynamic_name = $dynamic_name;
        $new->arguments = $arguments;
        return $new;
    }
    /**
     * @deprecated since Twig 3.12, use withDynamicArguments() instead
     */
    public function set_arguments(array $arguments): void
    {
        trigger_deprecation('twig/twig', '3.12', 'The "%s::setArguments()" method is deprecated, use "%s::withDynamicArguments()" instead.', static::class, static::class);
        $this->arguments = $arguments;
    }
    public function get_arguments(): array
    {
        return $this->arguments;
    }
    public function is_variadic(): bool
    {
        return $this->options['is_variadic'];
    }
    public function is_deprecated(): bool
    {
        return (bool) $this->options['deprecation_info'];
    }
    public function trigger_deprecation(?string $file = null, ?int $line = null): void
    {
        $this->options['deprecation_info']->trigger_deprecation($file, $line);
    }
    /**
     * @deprecated since Twig 3.15
     */
    public function get_deprecating_package(): string
    {
        trigger_deprecation('twig/twig', '3.15', 'The "%s" method is deprecated, use "%s::triggerDeprecation()" instead.', __METHOD__, static::class);
        return $this->options['deprecating_package'];
    }
    /**
     * @deprecated since Twig 3.15
     */
    public function get_deprecated_version(): string
    {
        trigger_deprecation('twig/twig', '3.15', 'The "%s" method is deprecated, use "%s::triggerDeprecation()" instead.', __METHOD__, static::class);
        return \is_bool($this->options['deprecated']) ? '' : $this->options['deprecated'];
    }
    /**
     * @deprecated since Twig 3.15
     */
    public function get_alternative(): ?string
    {
        trigger_deprecation('twig/twig', '3.15', 'The "%s" method is deprecated, use "%s::triggerDeprecation()" instead.', __METHOD__, static::class);
        return $this->options['alternative'];
    }
    public function get_minimal_number_of_required_arguments(): int
    {
        return ($this->options['needs_charset'] ? 1 : 0) + ($this->options['needs_environment'] ? 1 : 0) + ($this->options['needs_context'] ? 1 : 0) + \count($this->arguments);
    }
}