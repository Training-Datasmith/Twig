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
use Twig\Lexer;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Binary\Concat_Binary;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Empty_Expression;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
final class Literal_Expression_Parser extends Abstract_Expression_Parser implements Prefix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    public function parse(Parser $parser, Token $token): Abstract_Expression
    {
        $stream = $parser->get_stream();
        switch (true) {
            case $token->test(Token::NAME_TYPE):
                $stream->next();
                return match ($token->get_value()) {
                    'true', 'TRUE' => new Constant_Expression(true, $token->get_line()),
                    'false', 'FALSE' => new Constant_Expression(false, $token->get_line()),
                    'none', 'NONE', 'null', 'NULL' => new Constant_Expression(null, $token->get_line()),
                    default => new Context_Variable($token->get_value(), $token->get_line()),
                };
            case $token->test(Token::NUMBER_TYPE):
                $stream->next();
                return new Constant_Expression($token->get_value(), $token->get_line());
            case $token->test(Token::STRING_TYPE):
            case $token->test(Token::INTERPOLATION_START_TYPE):
                return $this->parse_string_expression($parser);
            case $token->test(Token::PUNCTUATION_TYPE):
                // In 4.0, we should always return the node or throw an error for default
                if ($node = match ($token->get_value()) {
                    '{' => $this->parse_mapping_expression($parser),
                    default => null,
                }) {
                    return $node;
                }
            // no break
            case $token->test(Token::OPERATOR_TYPE):
                if ('[' === $token->get_value()) {
                    return $this->parse_sequence_expression($parser);
                }
                if (preg_match(Lexer::REGEX_NAME, (string) $token->get_value(), $matches) && $matches[0] == $token->get_value()) {
                    // in this context, string operators are variable names
                    $stream->next();
                    return new Context_Variable($token->get_value(), $token->get_line());
                }
            // no break
            default:
                throw new Syntax_Error(\sprintf('Unexpected token "%s" of value "%s".', $token->to_english(), $token->get_value()), $token->get_line(), $stream->get_source_context());
        }
    }
    public function get_name(): string
    {
        return 'literal';
    }
    public function get_operator_tokens(): array
    {
        return [];
    }
    public function get_description(): string
    {
        return 'A literal value (boolean, string, number, sequence, mapping, ...)';
    }
    public function get_precedence(): int
    {
        // not used
        return 0;
    }
    private function parse_string_expression(Parser $parser)
    {
        $stream = $parser->get_stream();
        $nodes = [];
        // a string cannot be followed by another string in a single expression
        $next_can_be_string = true;
        while (true) {
            if ($next_can_be_string && $token = $stream->next_if(Token::STRING_TYPE)) {
                $nodes[] = new Constant_Expression($token->get_value(), $token->get_line());
                $next_can_be_string = false;
            } elseif ($stream->next_if(Token::INTERPOLATION_START_TYPE)) {
                $nodes[] = $parser->parse_expression();
                $stream->expect(Token::INTERPOLATION_END_TYPE);
                $next_can_be_string = true;
            } else {
                break;
            }
        }
        $expr = array_shift($nodes);
        foreach ($nodes as $node) {
            $expr = new Concat_Binary($expr, $node, $node->get_template_line());
        }
        return $expr;
    }
    private function parse_sequence_expression(Parser $parser): \Twig\Node\Expression\Array_Expression
    {
        $stream = $parser->get_stream();
        $stream->expect(Token::OPERATOR_TYPE, '[', 'A sequence element was expected');
        $node = new Array_Expression([], $stream->get_current()->get_line());
        $first = true;
        while (!$stream->test(Token::PUNCTUATION_TYPE, ']')) {
            if (!$first) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'A sequence element must be followed by a comma');
                // trailing ,?
                if ($stream->test(Token::PUNCTUATION_TYPE, ']')) {
                    break;
                }
            }
            $first = false;
            // Check for empty slots (comma with no expression)
            if ($stream->test(Token::PUNCTUATION_TYPE, ',')) {
                $node->add_element(new Empty_Expression($stream->get_current()->get_line()));
            } else {
                $node->add_element($parser->parse_expression());
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ']', 'An opened sequence is not properly closed');
        return $node;
    }
    private function parse_mapping_expression(Parser $parser): \Twig\Node\Expression\Array_Expression
    {
        $stream = $parser->get_stream();
        $stream->expect(Token::PUNCTUATION_TYPE, '{', 'A mapping element was expected');
        $node = new Array_Expression([], $stream->get_current()->get_line());
        $first = true;
        while (!$stream->test(Token::PUNCTUATION_TYPE, '}')) {
            if (!$first) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'A mapping value must be followed by a comma');
                // trailing ,?
                if ($stream->test(Token::PUNCTUATION_TYPE, '}')) {
                    break;
                }
            }
            $first = false;
            if ($stream->test(Token::OPERATOR_TYPE, '...')) {
                $node->add_element($parser->parse_expression());
                continue;
            }
            // a mapping key can be:
            //
            //  * a number -- 12
            //  * a string -- 'a'
            //  * a name, which is equivalent to a string -- a
            //  * an expression, which must be enclosed in parentheses -- (1 + 2)
            if ($token = $stream->next_if(Token::NAME_TYPE)) {
                $key = new Constant_Expression($token->get_value(), $token->get_line());
                // {a} is a shortcut for {a:a}
                if ($stream->test(Token::PUNCTUATION_TYPE, [',', '}'])) {
                    $value = new Context_Variable($key->get_attribute('value'), $key->get_template_line());
                    $node->add_element($value, $key);
                    continue;
                }
            } elseif (($token = $stream->next_if(Token::STRING_TYPE)) || $token = $stream->next_if(Token::NUMBER_TYPE)) {
                $key = new Constant_Expression($token->get_value(), $token->get_line());
            } elseif ($stream->test(Token::OPERATOR_TYPE, '(')) {
                $key = $parser->parse_expression();
            } else {
                $current = $stream->get_current();
                throw new Syntax_Error(\sprintf('A mapping key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "%s" of value "%s".', $current->to_english(), $current->get_value()), $current->get_line(), $stream->get_source_context());
            }
            $stream->expect(Token::PUNCTUATION_TYPE, ':', 'A mapping key must be followed by a colon (:)');
            $value = $parser->parse_expression();
            $node->add_element($value, $key);
        }
        $stream->expect(Token::PUNCTUATION_TYPE, '}', 'An opened mapping is not properly closed');
        return $node;
    }
}