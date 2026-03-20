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

use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Binary\Set_Binary;
use Twig\Node\Expression\Unary\Spread_Unary;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\Expression\Variable\Local_Variable;
use Twig\Node\Nodes;
use Twig\Parser;
use Twig\Token;
trait Arguments_Trait
{
    private function parse_callable_arguments(Parser $parser, int $line, bool $parse_open_parenthesis = true): Array_Expression
    {
        $arguments = new Array_Expression([], $line);
        foreach ($this->parse_named_arguments($parser, $parse_open_parenthesis) as $k => $n) {
            $arguments->add_element($n, new Local_Variable($k, $line));
        }
        return $arguments;
    }
    private function parse_named_arguments(Parser $parser, bool $parse_open_parenthesis = true): Nodes
    {
        $args = [];
        $stream = $parser->get_stream();
        if ($parse_open_parenthesis) {
            $stream->expect(Token::OPERATOR_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');
        }
        $has_spread = false;
        while (!$stream->test(Token::PUNCTUATION_TYPE, ')')) {
            if ($args) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');
                // if the comma above was a trailing comma, early exit the argument parse loop
                if ($stream->test(Token::PUNCTUATION_TYPE, ')')) {
                    break;
                }
            }
            $value = $parser->parse_expression();
            if ($value instanceof Spread_Unary) {
                $has_spread = true;
            } elseif ($has_spread) {
                throw new Syntax_Error('Normal arguments must be placed before argument unpacking.', $stream->get_current()->get_line(), $stream->get_source_context());
            }
            $name = null;
            if ($value instanceof Set_Binary) {
                $name = $value->get_node('left')->get_attribute('name');
                $value = $value->get_node('right');
            } elseif (($token = $stream->next_if(Token::OPERATOR_TYPE, '=')) || $token = $stream->next_if(Token::PUNCTUATION_TYPE, ':')) {
                if (!$value instanceof Context_Variable) {
                    throw new Syntax_Error(\sprintf('A parameter name must be a string, "%s" given.', $value::class), $token->get_line(), $stream->get_source_context());
                }
                $name = $value->get_attribute('name');
                $value = $parser->parse_expression();
            }
            if (null === $name) {
                $args[] = $value;
            } else {
                $args[$name] = $value;
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');
        return new Nodes($args);
    }
}