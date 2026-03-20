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

use Twig\Expression_Parser\Abstract_Expression_Parser;
use Twig\Expression_Parser\Expression_Parser_Description_Interface;
use Twig\Expression_Parser\Infix_Associativity;
use Twig\Expression_Parser\Infix_Expression_Parser_Interface;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Get_Attr_Expression;
use Twig\Node\Nodes;
use Twig\Parser;
use Twig\Template;
use Twig\Token;
/**
 * @internal
 */
final class Square_Bracket_Expression_Parser extends Abstract_Expression_Parser implements Infix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    public function parse(Parser $parser, Abstract_Expression $expr, Token $token): Abstract_Expression
    {
        $stream = $parser->get_stream();
        $lineno = $token->get_line();
        $arguments = new Array_Expression([], $lineno);
        // slice?
        $slice = false;
        if ($stream->test(Token::PUNCTUATION_TYPE, ':')) {
            $slice = true;
            $attribute = new Constant_Expression(0, $token->get_line());
        } else {
            $attribute = $parser->parse_expression();
        }
        if ($stream->next_if(Token::PUNCTUATION_TYPE, ':')) {
            $slice = true;
        }
        if ($slice) {
            if ($stream->test(Token::PUNCTUATION_TYPE, ']')) {
                $length = new Constant_Expression(null, $token->get_line());
            } else {
                $length = $parser->parse_expression();
            }
            $filter = $parser->get_filter('slice', $token->get_line());
            $arguments = new Nodes([$attribute, $length]);
            $filter = new ($filter->get_node_class())($expr, $filter, $arguments, $token->get_line());
            $stream->expect(Token::PUNCTUATION_TYPE, ']');
            return $filter;
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ']');
        return new Get_Attr_Expression($expr, $attribute, $arguments, Template::ARRAY_CALL, $lineno);
    }
    public function get_name(): string
    {
        return '[';
    }
    public function get_description(): string
    {
        return 'Array access';
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