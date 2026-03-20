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
use Twig\Expression_Parser\Abstract_Expression_Parser;
use Twig\Expression_Parser\Expression_Parser_Description_Interface;
use Twig\Expression_Parser\Infix_Associativity;
use Twig\Expression_Parser\Infix_Expression_Parser_Interface;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Macro_Reference_Expression;
use Twig\Node\Expression\Name_Expression;
use Twig\Node\Nodes;
use Twig\Parser;
use Twig\Token;
use Twig\Twig_Test;
/**
 * @internal
 */
class Is_Expression_Parser extends Abstract_Expression_Parser implements Infix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    use Arguments_Trait;
    private array $ready_nodes = [];
    public function parse(Parser $parser, Abstract_Expression $expr, Token $token): Abstract_Expression
    {
        $stream = $parser->get_stream();
        $test = $parser->get_test($token->get_line());
        $arguments = null;
        if ($stream->test(Token::OPERATOR_TYPE, '(')) {
            $arguments = $this->parse_named_arguments($parser);
        } elseif ($test->has_one_mandatory_argument()) {
            $arguments = new Nodes([0 => $parser->parse_expression($this->get_precedence())]);
        }
        if ('defined' === $test->get_name() && $expr instanceof Name_Expression && null !== $alias = $parser->get_imported_symbol('function', $expr->get_attribute('name'))) {
            $expr = new Macro_Reference_Expression($alias['node']->get_node('var'), $alias['name'], new Array_Expression([], $expr->get_template_line()), $expr->get_template_line());
        }
        $ready = $test instanceof Twig_Test;
        if (!isset($this->ready_nodes[$class = $test->get_node_class()])) {
            $this->ready_nodes[$class] = (bool) (new \ReflectionClass($class))->get_constructor()->get_attributes(First_Class_Twig_Callable_Ready::class);
        }
        if (!$ready = $this->ready_nodes[$class]) {
            trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigTest" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }
        return new $class($expr, $ready ? $test : $test->get_name(), $arguments, $stream->get_current()->get_line());
    }
    public function get_precedence(): int
    {
        return 100;
    }
    public function get_name(): string
    {
        return 'is';
    }
    public function get_description(): string
    {
        return 'Twig tests';
    }
    public function get_associativity(): Infix_Associativity
    {
        return Infix_Associativity::Left;
    }
}