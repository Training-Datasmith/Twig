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

use Twig\Environment;
use Twig\File_Extension_Escaping_Strategy;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Filter\Raw_Filter;
use Twig\Node\Node;
use Twig\Node_Visitor\Escaper_Node_Visitor;
use Twig\Runtime\Escaper_Runtime;
use Twig\Token_Parser\Auto_Escape_Token_Parser;
use Twig\Twig_Filter;
final class Escaper_Extension extends Abstract_Extension
{
    private ?\Twig\Environment $environment = null;
    private array $escapers = [];
    private $escaper;
    private $default_strategy;
    /**
     * @param string|false|callable $defaultStrategy An escaping strategy
     *
     * @see setDefaultStrategy()
     */
    public function __construct($default_strategy = 'html')
    {
        $this->set_default_strategy($default_strategy);
    }
    public function get_token_parsers(): array
    {
        return [new Auto_Escape_Token_Parser()];
    }
    public function get_node_visitors(): array
    {
        return [new Escaper_Node_Visitor()];
    }
    public function get_filters(): array
    {
        return [new Twig_Filter('escape', [Escaper_Runtime::class, 'escape'], ['is_safe_callback' => self::escape_filter_is_safe(...)]), new Twig_Filter('e', [Escaper_Runtime::class, 'escape'], ['is_safe_callback' => self::escape_filter_is_safe(...)]), new Twig_Filter('raw', null, ['is_safe' => ['all'], 'node_class' => Raw_Filter::class])];
    }
    public function get_last_modified(): int
    {
        return max(parent::get_last_modified(), filemtime((new \ReflectionClass(Escaper_Runtime::class))->get_file_name()));
    }
    /**
     * @deprecated since Twig 3.10
     */
    public function set_environment(Environment $environment): void
    {
        $trigger_deprecation = \func_num_args() > 1 ? func_get_arg(1) : true;
        if ($trigger_deprecation) {
            trigger_deprecation('twig/twig', '3.10', 'The "%s()" method is deprecated and not needed if you are using methods from "Twig\Runtime\EscaperRuntime".', __METHOD__);
        }
        $this->environment = $environment;
        $this->escaper = $environment->get_runtime(Escaper_Runtime::class);
    }
    /**
     * @deprecated since Twig 3.10
     */
    public function set_escaper_runtime(Escaper_Runtime $escaper): void
    {
        trigger_deprecation('twig/twig', '3.10', 'The "%s()" method is deprecated and not needed if you are using methods from "Twig\Runtime\EscaperRuntime".', __METHOD__);
        $this->escaper = $escaper;
    }
    /**
     * Sets the default strategy to use when not defined by the user.
     *
     * The strategy can be a valid PHP callback that takes the template
     * name as an argument and returns the strategy to use.
     *
     * @param string|false|callable(string $templateName): string $defaultStrategy An escaping strategy
     */
    public function set_default_strategy($default_strategy): void
    {
        if ('name' === $default_strategy) {
            $default_strategy = File_Extension_Escaping_Strategy::guess(...);
        }
        $this->default_strategy = $default_strategy;
    }
    /**
     * Gets the default strategy to use when not defined by the user.
     *
     * @param string $name The template name
     *
     * @return string|false The default strategy to use for the template
     */
    public function get_default_strategy(string $name)
    {
        // disable string callables to avoid calling a function named html or js,
        // or any other upcoming escaping strategy
        if (!\is_string($this->default_strategy) && false !== $this->default_strategy) {
            return \call_user_func($this->default_strategy, $name);
        }
        return $this->default_strategy;
    }
    /**
     * Defines a new escaper to be used via the escape filter.
     *
     * @param string                                        $strategy The strategy name that should be used as a strategy in the escape call
     * @param callable(Environment, string, string): string $callable A valid PHP callable
     *
     *
     * @deprecated since Twig 3.10
     */
    public function set_escaper($strategy, callable $callable): void
    {
        trigger_deprecation('twig/twig', '3.10', 'The "%s()" method is deprecated, use the "Twig\Runtime\EscaperRuntime::setEscaper()" method instead (be warned that Environment is not passed anymore to the callable).', __METHOD__);
        if (!isset($this->environment)) {
            throw new \LogicException(\sprintf('You must call "setEnvironment()" before calling "%s()".', __METHOD__));
        }
        $this->escapers[$strategy] = $callable;
        $callable = fn($string, $charset) => $callable($this->environment, $string, $charset);
        $this->escaper->set_escaper($strategy, $callable);
    }
    /**
     * Gets all defined escapers.
     *
     * @return array<string, callable(Environment, string, string): string> An array of escapers
     *
     * @deprecated since Twig 3.10
     */
    public function get_escapers()
    {
        trigger_deprecation('twig/twig', '3.10', 'The "%s()" method is deprecated, use the "Twig\Runtime\EscaperRuntime::getEscaper()" method instead.', __METHOD__);
        return $this->escapers;
    }
    /**
     * @deprecated since Twig 3.10
     */
    public function set_safe_classes(array $safe_classes = []): void
    {
        trigger_deprecation('twig/twig', '3.10', 'The "%s()" method is deprecated, use the "Twig\Runtime\EscaperRuntime::setSafeClasses()" method instead.', __METHOD__);
        if (!isset($this->escaper)) {
            throw new \LogicException(\sprintf('You must call "setEnvironment()" before calling "%s()".', __METHOD__));
        }
        $this->escaper->set_safe_classes($safe_classes);
    }
    /**
     * @deprecated since Twig 3.10
     */
    public function add_safe_class(string $class, array $strategies): void
    {
        trigger_deprecation('twig/twig', '3.10', 'The "%s()" method is deprecated, use the "Twig\Runtime\EscaperRuntime::addSafeClass()" method instead.', __METHOD__);
        if (!isset($this->escaper)) {
            throw new \LogicException(\sprintf('You must call "setEnvironment()" before calling "%s()".', __METHOD__));
        }
        $this->escaper->add_safe_class($class, $strategies);
    }
    /**
     * @internal
     *
     * @return array<string>
     */
    public static function escape_filter_is_safe(Node $filter_args): array
    {
        foreach ($filter_args as $arg) {
            if ($arg instanceof Constant_Expression) {
                return [$arg->get_attribute('value')];
            }
            return [];
        }
        return ['html'];
    }
}