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
use Twig\Node\Expression\Arrow_Function_Expression;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
final class Arrow_Expression_Parser extends Abstract_Expression_Parser implements Infix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    public function parse(Parser $parser, Abstract_Expression $expr, Token $token): \Twig\Node\Expression\Arrow_Function_Expression
    {
        // As the expression of the arrow function is independent from the current precedence, we want a precedence of 0
        return new Arrow_Function_Expression($parser->parse_expression(), $expr, $token->get_line());
    }
    public function get_name(): string
    {
        return '=>';
    }
    public function get_description(): string
    {
        return 'Arrow function (x => expr)';
    }
    public function get_precedence(): int
    {
        return 250;
    }
    public function get_associativity(): Infix_Associativity
    {
        return Infix_Associativity::Left;
    }
}