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
namespace Twig\Token_Parser;

use Twig\Error\Syntax_Error;
use Twig\Node\Types_Node;
use Twig\Token;
use Twig\Token_Stream;
/**
 * Declare variable types.
 *
 *  {% types {foo: 'number', bar?: 'string'} %}
 *
 * @author Jeroen Versteeg <jeroen@alisqi.com>
 *
 * @internal
 */
final class Types_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Types_Node
    {
        $stream = $this->parser->get_stream();
        $types = $this->parse_simple_mapping_expression($stream);
        $stream->expect(Token::BLOCK_END_TYPE);
        return new Types_Node($types, $token->get_line());
    }
    /**
     * @return array<string, array{type: string, optional: bool}>
     *
     * @throws SyntaxError
     */
    private function parse_simple_mapping_expression(Token_Stream $stream): array
    {
        $enclosed = null !== $stream->next_if(Token::PUNCTUATION_TYPE, '{');
        $types = [];
        $first = true;
        while (!($stream->test(Token::PUNCTUATION_TYPE, '}') || $stream->test(Token::BLOCK_END_TYPE))) {
            if (!$first) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'A type string must be followed by a comma');
                // trailing ,?
                if ($stream->test(Token::BLOCK_END_TYPE) || $stream->test(Token::PUNCTUATION_TYPE, '}')) {
                    break;
                }
            }
            $first = false;
            $name_token = $stream->expect(Token::NAME_TYPE);
            if ($stream->next_if(Token::OPERATOR_TYPE, '?:')) {
                $is_optional = true;
            } else {
                $is_optional = null !== $stream->next_if(Token::OPERATOR_TYPE, '?');
                $stream->expect(Token::PUNCTUATION_TYPE, ':', 'A type name must be followed by a colon (:)');
            }
            $value_token = $stream->expect(Token::STRING_TYPE);
            $types[$name_token->get_value()] = ['type' => $value_token->get_value(), 'optional' => $is_optional];
        }
        if ($enclosed) {
            $stream->expect(Token::PUNCTUATION_TYPE, '}', 'An opened mapping is not properly closed');
        }
        return $types;
    }
    public function get_tag(): string
    {
        return 'types';
    }
}