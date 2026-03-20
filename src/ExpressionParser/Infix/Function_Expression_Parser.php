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
namespace Twig\Expression_Parser\Infix;

use Twig\Attribute\First_Class_Twig_Callable_Ready;
use Twig\Error\Syntax_Error;
use Twig\Expression_Parser\Abstract_Expression_Parser;
use Twig\Expression_Parser\Expression_Parser_Description_Interface;
use Twig\Expression_Parser\Infix_Associativity;
use Twig\Expression_Parser\Infix_Expression_Parser_Interface;
use Twig\Node\Empty_Node;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Macro_Reference_Expression;
use Twig\Node\Expression\Name_Expression;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
final class Function_Expression_Parser extends Abstract_Expression_Parser implements Infix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    use Arguments_Trait;
    private array $ready_nodes = [];
    public function parse(Parser $parser, Abstract_Expression $expr, Token $token): Abstract_Expression
    {
        $line = $token->get_line();
        if (!$expr instanceof Name_Expression) {
            throw new Syntax_Error('Function name must be an identifier.', $line, $parser->get_stream()->get_source_context());
        }
        $name = $expr->get_attribute('name');
        if (null !== $alias = $parser->get_imported_symbol('function', $name)) {
            return new Macro_Reference_Expression($alias['node']->get_node('var'), $alias['name'], $this->parse_callable_arguments($parser, $line, false), $line);
        }
        $args = $this->parse_named_arguments($parser, false);
        $function = $parser->get_function($name, $line);
        if ($function->get_parser_callable()) {
            $fake_node = new Empty_Node($line);
            $fake_node->set_source_context($parser->get_stream()->get_source_context());
            return $function->get_parser_callable()($parser, $fake_node, $args, $line);
        }
        if (!isset($this->ready_nodes[$class = $function->get_node_class()])) {
            $this->ready_nodes[$class] = (bool) (new \ReflectionClass($class))->get_constructor()->get_attributes(First_Class_Twig_Callable_Ready::class);
        }
        if (!$ready = $this->ready_nodes[$class]) {
            trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigFunction" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }
        return new $class($ready ? $function : $function->get_name(), $args, $line);
    }
    public function get_name(): string
    {
        return '(';
    }
    public function get_description(): string
    {
        return 'Twig function call';
    }
    public function get_precedence(): int
    {
        return 512;
    }
    public function get_associativity(): Infix_Associativity
    {
        return Infix_Associativity::Left;
    }
}