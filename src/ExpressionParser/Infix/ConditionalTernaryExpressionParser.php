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
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Ternary\Conditional_Ternary;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
final class Conditional_Ternary_Expression_Parser extends Abstract_Expression_Parser implements Infix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    public function parse(Parser $parser, Abstract_Expression $left, Token $token): \Twig\Node\Expression\Ternary\Conditional_Ternary
    {
        $then = $parser->parse_expression($this->get_precedence());
        if ($parser->get_stream()->next_if(Token::PUNCTUATION_TYPE, ':')) {
            // Ternary operator (expr ? expr2 : expr3)
            $else = $parser->parse_expression($this->get_precedence());
        } else {
            // Ternary without else (expr ? expr2)
            $else = new Constant_Expression('', $token->get_line());
        }
        return new Conditional_Ternary($left, $then, $else, $token->get_line());
    }
    public function get_name(): string
    {
        return '?';
    }
    public function get_description(): string
    {
        return 'Conditional operator (a ? b : c)';
    }
    public function get_precedence(): int
    {
        return 0;
    }
    public function get_associativity(): Infix_Associativity
    {
        return Infix_Associativity::Left;
    }
}