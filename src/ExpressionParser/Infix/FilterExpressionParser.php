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
use Twig\Expression_Parser\Precedence_Change;
use Twig\Node\Empty_Node;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
final class Filter_Expression_Parser extends Abstract_Expression_Parser implements Infix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    use Arguments_Trait;
    private array $ready_nodes = [];
    public function parse(Parser $parser, Abstract_Expression $expr, Token $token): Abstract_Expression
    {
        $stream = $parser->get_stream();
        $token = $stream->expect(Token::NAME_TYPE);
        $line = $token->get_line();
        if (!$stream->test(Token::OPERATOR_TYPE, '(')) {
            $arguments = new Empty_Node();
        } else {
            $arguments = $this->parse_named_arguments($parser);
        }
        $filter = $parser->get_filter($token->get_value(), $line);
        $ready = true;
        if (!isset($this->ready_nodes[$class = $filter->get_node_class()])) {
            $this->ready_nodes[$class] = (bool) (new \ReflectionClass($class))->get_constructor()->get_attributes(First_Class_Twig_Callable_Ready::class);
        }
        if (!$ready = $this->ready_nodes[$class]) {
            trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigFilter" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }
        return new $class($expr, $ready ? $filter : new Constant_Expression($filter->get_name(), $line), $arguments, $line);
    }
    public function get_name(): string
    {
        return '|';
    }
    public function get_description(): string
    {
        return 'Twig filter call';
    }
    public function get_precedence(): int
    {
        return 512;
    }
    public function get_precedence_change(): \Twig\Expression_Parser\Precedence_Change
    {
        return new Precedence_Change('twig/twig', '3.21', 300);
    }
    public function get_associativity(): Infix_Associativity
    {
        return Infix_Associativity::Left;
    }
}