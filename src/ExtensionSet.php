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

use Twig\Error\Runtime_Error;
use Twig\Expression_Parser\Expression_Parsers;
use Twig\Expression_Parser\Infix\Binary_Operator_Expression_Parser;
use Twig\Expression_Parser\Infix_Associativity;
use Twig\Expression_Parser\Infix_Expression_Parser_Interface;
use Twig\Expression_Parser\Precedence_Change;
use Twig\Expression_Parser\Prefix\Unary_Operator_Expression_Parser;
use Twig\Extension\Attribute_Extension;
use Twig\Extension\Extension_Interface;
use Twig\Extension\Globals_Interface;
use Twig\Extension\Last_Modified_Extension_Interface;
use Twig\Extension\Staging_Extension;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node_Visitor\Node_Visitor_Interface;
use Twig\Token_Parser\Token_Parser_Interface;
// Help opcache.preload discover always-needed symbols
// @see https://github.com/php/php-src/issues/10131
class_exists(Binary_Operator_Expression_Parser::class);
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class Extension_Set
{
    private ?array $extensions = null;
    private bool $initialized = false;
    private bool $runtime_initialized = false;
    private readonly \Twig\Extension\Staging_Extension $staging;
    private ?array $parsers = null;
    private $visitors;
    /** @var array<string, TwigFilter> */
    private $filters;
    /** @var array<string, TwigFilter> */
    private $dynamic_filters;
    /** @var array<string, TwigTest> */
    private $tests;
    /** @var array<string, TwigTest> */
    private $dynamic_tests;
    /** @var array<string, TwigFunction> */
    private $functions;
    /** @var array<string, TwigFunction> */
    private $dynamic_functions;
    private Expression_Parsers $expression_parsers;
    /** @var array<string, mixed>|null */
    private ?array $globals = null;
    /** @var array<callable(string): (TwigFunction|false)> */
    private array $function_callbacks = [];
    /** @var array<callable(string): (TwigFilter|false)> */
    private array $filter_callbacks = [];
    /** @var array<callable(string): (TwigTest|false)> */
    private array $test_callbacks = [];
    /** @var array<callable(string): (TokenParserInterface|false)> */
    private array $parser_callbacks = [];
    private $last_modified = 0;
    public function __construct()
    {
        $this->staging = new Staging_Extension();
    }
    public function init_runtime(): void
    {
        $this->runtime_initialized = true;
    }
    public function has_extension(string $class): bool
    {
        return isset($this->extensions[ltrim($class, '\\')]);
    }
    public function get_extension(string $class): Extension_Interface
    {
        $class = ltrim($class, '\\');
        if (!isset($this->extensions[$class])) {
            throw new Runtime_Error(\sprintf('The "%s" extension is not enabled.', $class));
        }
        return $this->extensions[$class];
    }
    /**
     * @param ExtensionInterface[] $extensions
     */
    public function set_extensions(array $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->add_extension($extension);
        }
    }
    /**
     * @return ExtensionInterface[]
     */
    public function get_extensions(): array
    {
        return $this->extensions;
    }
    public function get_signature(): string
    {
        return json_encode(array_keys($this->extensions));
    }
    public function is_initialized(): bool
    {
        return $this->initialized || $this->runtime_initialized;
    }
    public function get_last_modified(): int
    {
        if (0 !== $this->last_modified) {
            return $this->last_modified;
        }
        $last_modified = 0;
        foreach ($this->extensions as $extension) {
            if ($extension instanceof Last_Modified_Extension_Interface) {
                $last_modified = max($extension->get_last_modified(), $last_modified);
            } else {
                $r = new \Reflection_Object($extension);
                if (is_file($r->get_file_name())) {
                    $last_modified = max(filemtime($r->get_file_name()), $last_modified);
                }
            }
        }
        return $this->last_modified = $last_modified;
    }
    public function add_extension(Extension_Interface $extension): void
    {
        if ($extension instanceof Attribute_Extension) {
            $class = $extension->get_class();
        } else {
            $class = $extension::class;
        }
        if ($this->initialized) {
            throw new \LogicException(\sprintf('Unable to register extension "%s" as extensions have already been initialized.', $class));
        }
        if (isset($this->extensions[$class])) {
            throw new \LogicException(\sprintf('Unable to register extension "%s" as it is already registered.', $class));
        }
        $this->extensions[$class] = $extension;
    }
    public function add_function(Twig_Function $function): void
    {
        if ($this->initialized) {
            throw new \LogicException(\sprintf('Unable to add function "%s" as extensions have already been initialized.', $function->get_name()));
        }
        $this->staging->add_function($function);
    }
    /**
     * @return TwigFunction[]
     */
    public function get_functions(): array
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        return $this->functions;
    }
    public function get_function(string $name): ?Twig_Function
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        if (isset($this->functions[$name])) {
            return $this->functions[$name];
        }
        foreach ($this->dynamic_functions as $pattern => $function) {
            if (preg_match($pattern, $name, $matches)) {
                array_shift($matches);
                return $function->with_dynamic_arguments($name, $function->get_name(), $matches);
            }
        }
        foreach ($this->function_callbacks as $callback) {
            if (false !== $function = $callback($name)) {
                return $function;
            }
        }
        return null;
    }
    /**
     * @param callable(string): (TwigFunction|false) $callable
     */
    public function register_undefined_function_callback(callable $callable): void
    {
        $this->function_callbacks[] = $callable;
    }
    public function add_filter(Twig_Filter $filter): void
    {
        if ($this->initialized) {
            throw new \LogicException(\sprintf('Unable to add filter "%s" as extensions have already been initialized.', $filter->get_name()));
        }
        $this->staging->add_filter($filter);
    }
    /**
     * @return TwigFilter[]
     */
    public function get_filters(): array
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        return $this->filters;
    }
    public function get_filter(string $name): ?Twig_Filter
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        if (isset($this->filters[$name])) {
            return $this->filters[$name];
        }
        foreach ($this->dynamic_filters as $pattern => $filter) {
            if (preg_match($pattern, $name, $matches)) {
                array_shift($matches);
                return $filter->with_dynamic_arguments($name, $filter->get_name(), $matches);
            }
        }
        foreach ($this->filter_callbacks as $callback) {
            if (false !== $filter = $callback($name)) {
                return $filter;
            }
        }
        return null;
    }
    /**
     * @param callable(string): (TwigFilter|false) $callable
     */
    public function register_undefined_filter_callback(callable $callable): void
    {
        $this->filter_callbacks[] = $callable;
    }
    public function add_node_visitor(Node_Visitor_Interface $visitor): void
    {
        if ($this->initialized) {
            throw new \LogicException('Unable to add a node visitor as extensions have already been initialized.');
        }
        $this->staging->add_node_visitor($visitor);
    }
    /**
     * @return NodeVisitorInterface[]
     */
    public function get_node_visitors(): array
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        return $this->visitors;
    }
    public function add_token_parser(Token_Parser_Interface $parser): void
    {
        if ($this->initialized) {
            throw new \LogicException('Unable to add a token parser as extensions have already been initialized.');
        }
        $this->staging->add_token_parser($parser);
    }
    /**
     * @return TokenParserInterface[]
     */
    public function get_token_parsers(): array
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        return $this->parsers;
    }
    public function get_token_parser(string $name): ?Token_Parser_Interface
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        if (isset($this->parsers[$name])) {
            return $this->parsers[$name];
        }
        foreach ($this->parser_callbacks as $callback) {
            if (false !== $parser = $callback($name)) {
                return $parser;
            }
        }
        return null;
    }
    /**
     * @param callable(string): (TokenParserInterface|false) $callable
     */
    public function register_undefined_token_parser_callback(callable $callable): void
    {
        $this->parser_callbacks[] = $callable;
    }
    /**
     * @return array<string, mixed>
     */
    public function get_globals(): array
    {
        if (null !== $this->globals) {
            return $this->globals;
        }
        $globals = [];
        foreach ($this->extensions as $extension) {
            if (!$extension instanceof Globals_Interface) {
                continue;
            }
            $globals = array_merge($globals, $extension->get_globals());
        }
        if ($this->initialized) {
            $this->globals = $globals;
        }
        return $globals;
    }
    public function reset_globals(): void
    {
        $this->globals = null;
    }
    public function add_test(Twig_Test $test): void
    {
        if ($this->initialized) {
            throw new \LogicException(\sprintf('Unable to add test "%s" as extensions have already been initialized.', $test->get_name()));
        }
        $this->staging->add_test($test);
    }
    /**
     * @return TwigTest[]
     */
    public function get_tests(): array
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        return $this->tests;
    }
    public function get_test(string $name): ?Twig_Test
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        if (isset($this->tests[$name])) {
            return $this->tests[$name];
        }
        foreach ($this->dynamic_tests as $pattern => $test) {
            if (preg_match($pattern, $name, $matches)) {
                array_shift($matches);
                return $test->with_dynamic_arguments($name, $test->get_name(), $matches);
            }
        }
        foreach ($this->test_callbacks as $callback) {
            if (false !== $test = $callback($name)) {
                return $test;
            }
        }
        return null;
    }
    /**
     * @param callable(string): (TwigTest|false) $callable
     */
    public function register_undefined_test_callback(callable $callable): void
    {
        $this->test_callbacks[] = $callable;
    }
    public function get_expression_parsers(): Expression_Parsers
    {
        if (!$this->initialized) {
            $this->init_extensions();
        }
        return $this->expression_parsers;
    }
    private function init_extensions(): void
    {
        $this->parsers = [];
        $this->filters = [];
        $this->functions = [];
        $this->tests = [];
        $this->dynamic_filters = [];
        $this->dynamic_functions = [];
        $this->dynamic_tests = [];
        $this->visitors = [];
        $this->expression_parsers = new Expression_Parsers();
        foreach ($this->extensions as $extension) {
            $this->init_extension($extension);
        }
        $this->init_extension($this->staging);
        // Done at the end only, so that an exception during initialization does not mark the environment as initialized when catching the exception
        $this->initialized = true;
    }
    private function init_extension(Extension_Interface $extension): void
    {
        // filters
        foreach ($extension->get_filters() as $filter) {
            $this->filters[$name = $filter->get_name()] = $filter;
            if (str_contains($name, '*')) {
                $this->dynamic_filters['#^' . str_replace('\*', '(.*?)', preg_quote($name, '#')) . '$#'] = $filter;
            }
        }
        // functions
        foreach ($extension->get_functions() as $function) {
            $this->functions[$name = $function->get_name()] = $function;
            if (str_contains($name, '*')) {
                $this->dynamic_functions['#^' . str_replace('\*', '(.*?)', preg_quote($name, '#')) . '$#'] = $function;
            }
        }
        // tests
        foreach ($extension->get_tests() as $test) {
            $this->tests[$name = $test->get_name()] = $test;
            if (str_contains($name, '*')) {
                $this->dynamic_tests['#^' . str_replace('\*', '(.*?)', preg_quote($name, '#')) . '$#'] = $test;
            }
        }
        // token parsers
        foreach ($extension->get_token_parsers() as $parser) {
            if (!$parser instanceof Token_Parser_Interface) {
                throw new \LogicException('getTokenParsers() must return an array of \Twig\TokenParser\TokenParserInterface.');
            }
            $this->parsers[$parser->get_tag()] = $parser;
        }
        // node visitors
        foreach ($extension->get_node_visitors() as $visitor) {
            $this->visitors[] = $visitor;
        }
        // expression parsers
        if (method_exists($extension, 'getExpressionParsers')) {
            $this->expression_parsers->add($extension->get_expression_parsers());
        }
        $operators = $extension->get_operators();
        if (!\is_array($operators)) {
            throw new \InvalidArgumentException(\sprintf('"%s::getOperators()" must return an array with operators, got "%s".', $extension::class, get_debug_type($operators) . (\is_resource($operators) ? '' : '#' . $operators)));
        }
        if (2 !== \count($operators)) {
            throw new \InvalidArgumentException(\sprintf('"%s::getOperators()" must return an array of 2 elements, got %d.', $extension::class, \count($operators)));
        }
        $expression_parsers = [];
        foreach ($operators[0] as $operator => $op) {
            $expression_parsers[] = new Unary_Operator_Expression_Parser($op['class'], $operator, $op['precedence'], $op['precedence_change'] ?? null, '', $op['aliases'] ?? []);
        }
        foreach ($operators[1] as $operator => $op) {
            $op['associativity'] = match ($op['associativity']) {
                1 => Infix_Associativity::Left,
                2 => Infix_Associativity::Right,
                default => throw new \InvalidArgumentException(\sprintf('Invalid associativity "%s" for operator "%s".', $op['associativity'], $operator)),
            };
            if (isset($op['callable'])) {
                $expression_parsers[] = $this->convert_infix_expression_parser($op['class'], $operator, $op['precedence'], $op['associativity'], $op['precedence_change'] ?? null, $op['aliases'] ?? [], $op['callable']);
            } else {
                $expression_parsers[] = new Binary_Operator_Expression_Parser($op['class'], $operator, $op['precedence'], $op['associativity'], $op['precedence_change'] ?? null, '', $op['aliases'] ?? []);
            }
        }
        if (\count($expression_parsers)) {
            trigger_deprecation('twig/twig', '3.21', \sprintf('Extension "%s" uses the old signature for "getOperators()", please implement "getExpressionParsers()" instead.', $extension::class));
            $this->expression_parsers->add($expression_parsers);
        }
    }
    private function convert_infix_expression_parser(string $node_class, string $operator, int $precedence, Infix_Associativity $associativity, ?Precedence_Change $precedence_change, array $aliases, callable $callable): Infix_Expression_Parser_Interface
    {
        trigger_deprecation('twig/twig', '3.21', \sprintf('Using a non-ExpressionParserInterface object to define the "%s" binary operator is deprecated.', $operator));
        return new class($node_class, $operator, $precedence, $associativity, $precedence_change, $aliases, $callable) extends Binary_Operator_Expression_Parser
        {
            public function __construct(string $node_class, string $operator, int $precedence, Infix_Associativity $associativity = Infix_Associativity::Left, ?Precedence_Change $precedence_change = null, array $aliases = [], private $callable = null)
            {
                parent::__construct($node_class, $operator, $precedence, $associativity, $precedence_change, $aliases);
            }
            public function parse(Parser $parser, Abstract_Expression $expr, Token $token): Abstract_Expression
            {
                return ($this->callable)($parser, $expr);
            }
        };
    }
}