<?php

declare (strict_types=1);
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Twig;

use Twig\Error\Syntax_Error;
use Twig\Expression_Parser\Expression_Parser_Interface;
use Twig\Expression_Parser\Expression_Parsers;
use Twig\Expression_Parser\Expression_Parser_Type;
use Twig\Expression_Parser\Infix_Expression_Parser_Interface;
use Twig\Expression_Parser\Prefix\Literal_Expression_Parser;
use Twig\Expression_Parser\Prefix_Expression_Parser_Interface;
use Twig\Node\Block_Node;
use Twig\Node\Block_Reference_Node;
use Twig\Node\Body_Node;
use Twig\Node\Empty_Node;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Variable\Assign_Template_Variable;
use Twig\Node\Expression\Variable\Template_Variable;
use Twig\Node\Macro_Node;
use Twig\Node\Module_Node;
use Twig\Node\Node;
use Twig\Node\Node_Capture_Interface;
use Twig\Node\Node_Output_Interface;
use Twig\Node\Nodes;
use Twig\Node\Print_Node;
use Twig\Node\Text_Node;
use Twig\Token_Parser\Token_Parser_Interface;
use Twig\Util\Reflection_Callable;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Parser
{
    private $stack = [];
    private ?\WeakMap $expression_refs = null;
    private $stream;
    private $parent;
    private $visitors;
    private $expression_parser;
    private $blocks;
    private $block_stack;
    private $macros;
    private $imported_symbols;
    private $traits;
    private $embedded_templates = [];
    private static int $embed_index = 1;
    private $var_name_salt = 0;
    private $ignore_unknown_twig_callables = false;
    private readonly Expression_Parsers $parsers;
    public function __construct(private readonly Environment $env)
    {
        $this->parsers = $env->get_expression_parsers();
    }
    public function get_environment(): Environment
    {
        return $this->env;
    }
    public function get_var_name(): string
    {
        trigger_deprecation('twig/twig', '3.15', 'The "%s()" method is deprecated.', __METHOD__);
        return \sprintf('__internal_parse_%d', $this->var_name_salt++);
    }
    /**
     * @throws SyntaxError
     */
    public function parse(Token_Stream $stream, $test = null, bool $drop_needle = false): Module_Node
    {
        $vars = get_object_vars($this);
        unset($vars['stack'], $vars['env'], $vars['handlers'], $vars['visitors'], $vars['expressionParser'], $vars['reservedMacroNames'], $vars['varNameSalt'], $vars['parsers']);
        $this->stack[] = $vars;
        // node visitors
        if (null === $this->visitors) {
            $this->visitors = $this->env->get_node_visitors();
        }
        $this->stream = $stream;
        $this->parent = null;
        $this->blocks = [];
        $this->macros = [];
        $this->traits = [];
        $this->block_stack = [];
        $this->imported_symbols = [[]];
        $this->embedded_templates = [];
        $this->expression_refs = new \WeakMap();
        try {
            $body = $this->subparse($test, $drop_needle);
            if (null !== $this->parent && null === $body = $this->filter_body_nodes($body)) {
                $body = new Empty_Node();
            }
        } catch (Syntax_Error $e) {
            if (!$e->get_source_context()) {
                $e->set_source_context($this->stream->get_source_context());
            }
            if (!$e->get_template_line()) {
                $e->set_template_line($this->get_current_token()->get_line());
            }
            throw $e;
        } finally {
            $this->expression_refs = null;
        }
        $node = new Module_Node(new Body_Node([$body]), $this->parent, $this->blocks ? new Nodes($this->blocks) : new Empty_Node(), $this->macros ? new Nodes($this->macros) : new Empty_Node(), $this->traits ? new Nodes($this->traits) : new Empty_Node(), $this->embedded_templates ? new Nodes($this->embedded_templates) : new Empty_Node(), $stream->get_source_context());
        $traverser = new Node_Traverser($this->env, $this->visitors);
        /**
         * @var ModuleNode $node
         */
        $node = $traverser->traverse($node);
        // restore previous stack so previous parse() call can resume working
        foreach (array_pop($this->stack) as $key => $val) {
            $this->{$key} = $val;
        }
        return $node;
    }
    public function should_ignore_unknown_twig_callables(): bool
    {
        return $this->ignore_unknown_twig_callables;
    }
    public function subparse_ignore_unknown_twig_callables($test, bool $drop_needle = false): void
    {
        $previous = $this->ignore_unknown_twig_callables;
        $this->ignore_unknown_twig_callables = true;
        try {
            $this->subparse($test, $drop_needle);
        } finally {
            $this->ignore_unknown_twig_callables = $previous;
        }
    }
    /**
     * @throws SyntaxError
     */
    public function subparse($test, bool $drop_needle = false): Node
    {
        $lineno = $this->get_current_token()->get_line();
        $rv = [];
        while (!$this->stream->is_eof()) {
            switch (true) {
                case $this->stream->get_current()->test(Token::TEXT_TYPE):
                    $token = $this->stream->next();
                    $rv[] = new Text_Node($token->get_value(), $token->get_line());
                    break;
                case $this->stream->get_current()->test(Token::VAR_START_TYPE):
                    $token = $this->stream->next();
                    $expr = $this->parse_expression();
                    $this->stream->expect(Token::VAR_END_TYPE);
                    $rv[] = new Print_Node($expr, $token->get_line());
                    break;
                case $this->stream->get_current()->test(Token::BLOCK_START_TYPE):
                    $this->stream->next();
                    $token = $this->get_current_token();
                    if (!$token->test(Token::NAME_TYPE)) {
                        throw new Syntax_Error('A block must start with a tag name.', $token->get_line(), $this->stream->get_source_context());
                    }
                    if (null !== $test && $test($token)) {
                        if ($drop_needle) {
                            $this->stream->next();
                        }
                        if (1 === \count($rv)) {
                            return $rv[0];
                        }
                        return new Nodes($rv, $lineno);
                    }
                    if (!$subparser = $this->env->get_token_parser($token->get_value())) {
                        if (null !== $test) {
                            $e = new Syntax_Error(\sprintf('Unexpected "%s" tag', $token->get_value()), $token->get_line(), $this->stream->get_source_context());
                            $callable = (new Reflection_Callable(new Twig_Test('decision', $test)))->get_callable();
                            if (\is_array($callable) && $callable[0] instanceof Token_Parser_Interface) {
                                $e->append_message(\sprintf(' (expecting closing tag for the "%s" tag defined near line %s).', $callable[0]->get_tag(), $lineno));
                            }
                        } else {
                            $e = new Syntax_Error(\sprintf('Unknown "%s" tag.', $token->get_value()), $token->get_line(), $this->stream->get_source_context());
                            $e->add_suggestions($token->get_value(), array_keys($this->env->get_token_parsers()));
                        }
                        throw $e;
                    }
                    $this->stream->next();
                    $subparser->set_parser($this);
                    $node = $subparser->parse($token);
                    if (!$node) {
                        trigger_deprecation('twig/twig', '3.12', 'Returning "null" from "%s" is deprecated and forbidden by "TokenParserInterface".', $subparser::class);
                    } else {
                        $node->set_node_tag($subparser->get_tag());
                        $rv[] = $node;
                    }
                    break;
                default:
                    throw new Syntax_Error('The lexer or the parser ended up in an unsupported state.', $this->get_current_token()->get_line(), $this->stream->get_source_context());
            }
        }
        if (1 === \count($rv)) {
            return $rv[0];
        }
        return new Nodes($rv, $lineno);
    }
    public function get_block_stack(): array
    {
        trigger_deprecation('twig/twig', '3.12', 'Method "%s()" is deprecated.', __METHOD__);
        return $this->block_stack;
    }
    /**
     * @return string|null
     */
    public function peek_block_stack()
    {
        return $this->block_stack[\count($this->block_stack) - 1] ?? null;
    }
    public function pop_block_stack(): void
    {
        array_pop($this->block_stack);
    }
    public function push_block_stack($name): void
    {
        $this->block_stack[] = $name;
    }
    public function has_block(string $name): bool
    {
        trigger_deprecation('twig/twig', '3.12', 'Method "%s()" is deprecated.', __METHOD__);
        return isset($this->blocks[$name]);
    }
    public function get_block(string $name): Node
    {
        trigger_deprecation('twig/twig', '3.12', 'Method "%s()" is deprecated.', __METHOD__);
        return $this->blocks[$name];
    }
    public function set_block(string $name, Block_Node $value): void
    {
        if (isset($this->blocks[$name])) {
            throw new Syntax_Error(\sprintf("The block '%s' has already been defined line %d.", $name, $this->blocks[$name]->get_template_line()), $this->get_current_token()->get_line(), $this->blocks[$name]->get_source_context());
        }
        $this->blocks[$name] = new Body_Node([$value], [], $value->get_template_line());
    }
    public function has_macro(string $name): bool
    {
        trigger_deprecation('twig/twig', '3.12', 'Method "%s()" is deprecated.', __METHOD__);
        return isset($this->macros[$name]);
    }
    public function set_macro(string $name, Macro_Node $node): void
    {
        $this->macros[$name] = $node;
    }
    public function add_trait($trait): void
    {
        $this->traits[] = $trait;
    }
    public function has_traits(): bool
    {
        trigger_deprecation('twig/twig', '3.12', 'Method "%s()" is deprecated.', __METHOD__);
        return \count($this->traits) > 0;
    }
    public function embed_template(Module_Node $template): void
    {
        $template->set_index(self::$embed_index++);
        $this->embedded_templates[] = $template;
    }
    public function add_imported_symbol(string $type, string $alias, ?string $name = null, Abstract_Expression|Assign_Template_Variable|null $internal_ref = null): void
    {
        if ($internal_ref && !$internal_ref instanceof Assign_Template_Variable) {
            trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance as an internal reference is deprecated ("%s" given).', __METHOD__, Assign_Template_Variable::class, $internal_ref::class);
            $internal_ref = new Assign_Template_Variable(new Template_Variable($internal_ref->get_attribute('name'), $internal_ref->get_template_line()), $internal_ref->get_attribute('global'));
        }
        $this->imported_symbols[0][$type][$alias] = ['name' => $name, 'node' => $internal_ref];
    }
    /**
     * @return array{name: string, node: AssignTemplateVariable|null}|null
     */
    public function get_imported_symbol(string $type, string $alias)
    {
        // if the symbol does not exist in the current scope (0), try in the main/global scope (last index)
        return $this->imported_symbols[0][$type][$alias] ?? $this->imported_symbols[\count($this->imported_symbols) - 1][$type][$alias] ?? null;
    }
    public function is_main_scope(): bool
    {
        return 1 === \count($this->imported_symbols);
    }
    public function push_local_scope(): void
    {
        array_unshift($this->imported_symbols, []);
    }
    public function pop_local_scope(): void
    {
        array_shift($this->imported_symbols);
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function get_expression_parser(): Expression_Parser
    {
        trigger_deprecation('twig/twig', '3.21', 'Method "%s()" is deprecated, use "parseExpression()" instead.', __METHOD__);
        if (null === $this->expression_parser) {
            $this->expression_parser = new Expression_Parser($this, $this->env);
        }
        return $this->expression_parser;
    }
    public function parse_expression(int $precedence = 0): Abstract_Expression
    {
        $token = $this->get_current_token();
        if ($token->test(Token::OPERATOR_TYPE) && $ep = $this->parsers->get_by_name(Prefix_Expression_Parser_Interface::class, $token->get_value())) {
            $this->get_stream()->next();
            $expr = $ep->parse($this, $token);
            $this->check_precedence_deprecations($ep, $expr);
        } else {
            $expr = $this->parsers->get_by_class(Literal_Expression_Parser::class)->parse($this, $token);
        }
        $token = $this->get_current_token();
        while ($token->test(Token::OPERATOR_TYPE) && ($ep = $this->parsers->get_by_name(Infix_Expression_Parser_Interface::class, $token->get_value())) && $ep->get_precedence() >= $precedence) {
            $this->get_stream()->next();
            $expr = $ep->parse($this, $expr, $token);
            $this->check_precedence_deprecations($ep, $expr);
            $token = $this->get_current_token();
        }
        return $expr;
    }
    public function get_parent(): ?Node
    {
        trigger_deprecation('twig/twig', '3.12', 'Method "%s()" is deprecated.', __METHOD__);
        return $this->parent;
    }
    public function has_inheritance(): bool
    {
        return $this->parent || 0 < \count($this->traits);
    }
    public function set_parent(?Node $parent): void
    {
        if (null === $parent) {
            trigger_deprecation('twig/twig', '3.12', 'Passing "null" to "%s()" is deprecated.', __METHOD__);
        }
        if (null !== $parent && !$parent instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.24', 'Passing a "%s" instance to "%s()" is deprecated, pass an "AbstractExpression" instance instead.', $parent::class, __METHOD__);
        }
        if (null !== $this->parent) {
            throw new Syntax_Error('Multiple extends tags are forbidden.', $parent->get_template_line(), $parent->get_source_context());
        }
        $this->parent = $parent;
    }
    public function get_stream(): Token_Stream
    {
        return $this->stream;
    }
    public function get_current_token(): Token
    {
        return $this->stream->get_current();
    }
    public function get_function(string $name, int $line): Twig_Function
    {
        try {
            $function = $this->env->get_function($name);
        } catch (Syntax_Error $e) {
            if (!$this->should_ignore_unknown_twig_callables()) {
                throw $e;
            }
            $function = null;
        }
        if (!$function) {
            if ($this->should_ignore_unknown_twig_callables()) {
                return new Twig_Function($name, static fn(): string => '');
            }
            $e = new Syntax_Error(\sprintf('Unknown "%s" function.', $name), $line, $this->stream->get_source_context());
            $e->add_suggestions($name, array_keys($this->env->get_functions()));
            throw $e;
        }
        if ($function->is_deprecated()) {
            $src = $this->stream->get_source_context();
            $function->trigger_deprecation($src->get_path() ?: $src->get_name(), $line);
        }
        return $function;
    }
    public function get_filter(string $name, int $line): Twig_Filter
    {
        try {
            $filter = $this->env->get_filter($name);
        } catch (Syntax_Error $e) {
            if (!$this->should_ignore_unknown_twig_callables()) {
                throw $e;
            }
            $filter = null;
        }
        if (!$filter) {
            if ($this->should_ignore_unknown_twig_callables()) {
                return new Twig_Filter($name, static fn(): string => '');
            }
            $e = new Syntax_Error(\sprintf('Unknown "%s" filter.', $name), $line, $this->stream->get_source_context());
            $e->add_suggestions($name, array_keys($this->env->get_filters()));
            throw $e;
        }
        if ($filter->is_deprecated()) {
            $src = $this->stream->get_source_context();
            $filter->trigger_deprecation($src->get_path() ?: $src->get_name(), $line);
        }
        return $filter;
    }
    public function get_test(int $line): Twig_Test
    {
        $name = $this->stream->expect(Token::NAME_TYPE)->get_value();
        if ($this->stream->test(Token::NAME_TYPE)) {
            // try 2-words tests
            $name = $name . ' ' . $this->get_current_token()->get_value();
            try {
                $test = $this->env->get_test($name);
            } catch (Syntax_Error $e) {
                if (!$this->should_ignore_unknown_twig_callables()) {
                    throw $e;
                }
                $test = null;
            }
            $this->stream->next();
        } else {
            try {
                $test = $this->env->get_test($name);
            } catch (Syntax_Error $e) {
                if (!$this->should_ignore_unknown_twig_callables()) {
                    throw $e;
                }
                $test = null;
            }
        }
        if (!$test) {
            if ($this->should_ignore_unknown_twig_callables()) {
                return new Twig_Test($name, static fn(): string => '');
            }
            $e = new Syntax_Error(\sprintf('Unknown "%s" test.', $name), $line, $this->stream->get_source_context());
            $e->add_suggestions($name, array_keys($this->env->get_tests()));
            throw $e;
        }
        if ($test->is_deprecated()) {
            $src = $this->stream->get_source_context();
            $test->trigger_deprecation($src->get_path() ?: $src->get_name(), $this->stream->get_current()->get_line());
        }
        return $test;
    }
    private function filter_body_nodes(Node $node, bool $nested = false): ?Node
    {
        // check that the body does not contain non-empty output nodes
        if ($node instanceof Text_Node && !ctype_space((string) $node->get_attribute('data')) || !$node instanceof Text_Node && !$node instanceof Block_Reference_Node && $node instanceof Node_Output_Interface) {
            if (str_contains((string) $node, \chr(0xef) . \chr(0xbb) . \chr(0xbf))) {
                $t = substr((string) $node->get_attribute('data'), 3);
                if ('' === $t || ctype_space($t)) {
                    // bypass empty nodes starting with a BOM
                    return null;
                }
            }
            throw new Syntax_Error('A template that extends another one cannot include content outside Twig blocks. Did you forget to put the content inside a {% block %} tag?', $node->get_template_line(), $this->stream->get_source_context());
        }
        // bypass nodes that "capture" the output
        if ($node instanceof Node_Capture_Interface) {
            // a "block" tag in such a node will serve as a block definition AND be displayed in place as well
            return $node;
        }
        // "block" tags that are not captured (see above) are only used for defining
        // the content of the block. In such a case, nesting it does not work as
        // expected as the definition is not part of the default template code flow.
        if ($nested && $node instanceof Block_Reference_Node) {
            throw new Syntax_Error('A block definition cannot be nested under non-capturing nodes.', $node->get_template_line(), $this->stream->get_source_context());
        }
        if ($node instanceof Node_Output_Interface) {
            return null;
        }
        // here, $nested means "being at the root level of a child template"
        // we need to discard the wrapping "Node" for the "body" node
        // Node::class !== \get_class($node) should be removed in Twig 4.0
        $nested = $nested || Node::class !== $node::class && !$node instanceof Nodes;
        foreach ($node as $k => $n) {
            if (null === $this->filter_body_nodes($n, $nested)) {
                $node->remove_node($k);
            }
        }
        return $node;
    }
    private function check_precedence_deprecations(Expression_Parser_Interface $expression_parser, Abstract_Expression $expr): void
    {
        $this->expression_refs[$expr] = $expression_parser;
        $precedence_changes = $this->parsers->get_precedence_changes();
        // Check that the all nodes that are between the 2 precedences have explicit parentheses
        if (!isset($precedence_changes[$expression_parser])) {
            return;
        }
        if ($expr->has_explicit_parentheses()) {
            return;
        }
        if ($expression_parser instanceof Prefix_Expression_Parser_Interface) {
            /** @var AbstractExpression $node */
            $node = $expr->get_node('node');
            foreach ($precedence_changes as $ep => $changes) {
                if (!\in_array($expression_parser, $changes, true)) {
                    continue;
                }
                if (isset($this->expression_refs[$node]) && $ep === $this->expression_refs[$node]) {
                    $change = $expression_parser->get_precedence_change();
                    trigger_deprecation($change->get_package(), $change->get_version(), \sprintf('As the "%s" %s operator will change its precedence in the next major version, add explicit parentheses to avoid behavior change in "%s" at line %d.', $expression_parser->get_name(), Expression_Parser_Type::get_type($expression_parser)->value, $this->get_stream()->get_source_context()->get_name(), $node->get_template_line()));
                }
            }
        }
        foreach ($precedence_changes[$expression_parser] as $ep) {
            foreach ($expr as $node) {
                /** @var AbstractExpression $node */
                if (isset($this->expression_refs[$node]) && $ep === $this->expression_refs[$node] && !$node->has_explicit_parentheses()) {
                    $change = $ep->get_precedence_change();
                    trigger_deprecation($change->get_package(), $change->get_version(), \sprintf('As the "%s" %s operator will change its precedence in the next major version, add explicit parentheses to avoid behavior change in "%s" at line %d.', $ep->get_name(), Expression_Parser_Type::get_type($ep)->value, $this->get_stream()->get_source_context()->get_name(), $node->get_template_line()));
                }
            }
        }
    }
}