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
namespace Twig\Expression_Parser\Prefix;

use Twig\Error\Syntax_Error;
use Twig\Expression_Parser\Abstract_Expression_Parser;
use Twig\Expression_Parser\Expression_Parser_Description_Interface;
use Twig\Expression_Parser\Prefix_Expression_Parser_Interface;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\List_Expression;
use Twig\Node\Expression\Variable\Assign_Context_Variable;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
final class Grouping_Expression_Parser extends Abstract_Expression_Parser implements Prefix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    public function parse(Parser $parser, Token $token): Abstract_Expression
    {
        $stream = $parser->get_stream();
        $expr = $parser->parse_expression($this->get_precedence());
        if ($stream->next_if(Token::PUNCTUATION_TYPE, ')')) {
            if (!$stream->test(Token::OPERATOR_TYPE, '=>')) {
                return $expr->set_explicit_parentheses();
            }
            return new List_Expression([self::to_assign_context_variable($expr)], $token->get_line());
        }
        // determine if we are parsing an arrow function arguments
        if (!$stream->test(Token::PUNCTUATION_TYPE, ',')) {
            $stream->expect(Token::PUNCTUATION_TYPE, ')', 'An opened parenthesis is not properly closed');
        }
        $names = [$expr];
        while (true) {
            if ($stream->next_if(Token::PUNCTUATION_TYPE, ')')) {
                break;
            }
            $stream->expect(Token::PUNCTUATION_TYPE, ',');
            $token = $stream->expect(Token::NAME_TYPE);
            $names[] = new Context_Variable($token->get_value(), $token->get_line());
        }
        if (!$stream->test(Token::OPERATOR_TYPE, '=>')) {
            throw new Syntax_Error('A list of variables must be followed by an arrow.', $stream->get_current()->get_line(), $stream->get_source_context());
        }
        return new List_Expression(array_map(self::to_assign_context_variable(...), $names), $token->get_line());
    }
    private static function to_assign_context_variable(Abstract_Expression $expr): Assign_Context_Variable
    {
        if (!$expr instanceof Context_Variable) {
            throw new Syntax_Error('A list must only contain variables.', $expr->get_template_line(), $expr->get_source_context());
        }
        return $expr instanceof Assign_Context_Variable ? $expr : new Assign_Context_Variable($expr->get_attribute('name'), $expr->get_template_line());
    }
    public function get_name(): string
    {
        return '(';
    }
    public function get_description(): string
    {
        return 'Explicit group expression (a)';
    }
    public function get_precedence(): int
    {
        return 0;
    }
}