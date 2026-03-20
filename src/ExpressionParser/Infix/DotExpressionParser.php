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
use Twig\Expression_Parser\Abstract_Expression_Parser;
use Twig\Expression_Parser\Expression_Parser_Description_Interface;
use Twig\Expression_Parser\Infix_Associativity;
use Twig\Expression_Parser\Infix_Expression_Parser_Interface;
use Twig\Lexer;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Get_Attr_Expression;
use Twig\Node\Expression\Macro_Reference_Expression;
use Twig\Node\Expression\Name_Expression;
use Twig\Node\Expression\Variable\Template_Variable;
use Twig\Parser;
use Twig\Template;
use Twig\Token;
/**
 * @internal
 */
final class Dot_Expression_Parser extends Abstract_Expression_Parser implements Infix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    use Arguments_Trait;
    public function parse(Parser $parser, Abstract_Expression $expr, Token $token): Abstract_Expression
    {
        $null_safe = '?.' === $token->get_value();
        $stream = $parser->get_stream();
        $token = $stream->get_current();
        $lineno = $token->get_line();
        $arguments = new Array_Expression([], $lineno);
        $type = Template::ANY_CALL;
        if ($stream->next_if(Token::OPERATOR_TYPE, '(')) {
            $attribute = $parser->parse_expression();
            $stream->expect(Token::PUNCTUATION_TYPE, ')');
        } else {
            $token = $stream->next();
            if ($token->test(Token::NAME_TYPE) || $token->test(Token::NUMBER_TYPE) || $token->test(Token::OPERATOR_TYPE) && preg_match(Lexer::REGEX_NAME, (string) $token->get_value())) {
                $attribute = new Constant_Expression($token->get_value(), $token->get_line());
            } else {
                throw new Syntax_Error(\sprintf('Expected name or number, got value "%s" of type "%s".', $token->get_value(), $token->to_english()), $token->get_line(), $stream->get_source_context());
            }
        }
        if ($stream->test(Token::OPERATOR_TYPE, '(')) {
            $type = Template::METHOD_CALL;
            $arguments = $this->parse_callable_arguments($parser, $token->get_line());
        }
        if ($expr instanceof Name_Expression && (null !== $parser->get_imported_symbol('template', $expr->get_attribute('name')) || '_self' === $expr->get_attribute('name') && $attribute instanceof Constant_Expression)) {
            return new Macro_Reference_Expression(new Template_Variable($expr->get_attribute('name'), $expr->get_template_line()), 'macro_' . $attribute->get_attribute('value'), $arguments, $expr->get_template_line());
        }
        return new Get_Attr_Expression($expr, $attribute, $arguments, $type, $lineno, $null_safe);
    }
    public function get_name(): string
    {
        return '.';
    }
    public function get_aliases(): array
    {
        return ['?.'];
    }
    public function get_description(): string
    {
        return 'Get an attribute on a variable';
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