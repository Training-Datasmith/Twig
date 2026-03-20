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
use Twig\Node\Nodes;
use Twig\Node\Set_Node;
use Twig\Token;
/**
 * Defines a variable.
 *
 *  {% set foo = 'foo' %}
 *  {% set foo = [1, 2] %}
 *  {% set foo = {'foo': 'bar'} %}
 *  {% set foo = 'foo' ~ 'bar' %}
 *  {% set foo, bar = 'foo', 'bar' %}
 *  {% set foo %}Some content{% endset %}
 *
 * @internal
 */
final class Set_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Set_Node
    {
        $lineno = $token->get_line();
        $stream = $this->parser->get_stream();
        $names = $this->parse_assignment_expression();
        $capture = false;
        if ($stream->next_if(Token::OPERATOR_TYPE, '=')) {
            $values = $this->parse_multitarget_expression();
            $stream->expect(Token::BLOCK_END_TYPE);
            if (\count($names) !== \count($values)) {
                throw new Syntax_Error('When using set, you must have the same number of variables and assignments.', $stream->get_current()->get_line(), $stream->get_source_context());
            }
        } else {
            $capture = true;
            if (\count($names) > 1) {
                throw new Syntax_Error('When using set with a block, you cannot have a multi-target.', $stream->get_current()->get_line(), $stream->get_source_context());
            }
            $stream->expect(Token::BLOCK_END_TYPE);
            $values = $this->parser->subparse($this->decide_block_end(...), true);
            $stream->expect(Token::BLOCK_END_TYPE);
        }
        return new Set_Node($capture, $names, $values, $lineno);
    }
    public function decide_block_end(Token $token): bool
    {
        return $token->test('endset');
    }
    public function get_tag(): string
    {
        return 'set';
    }
    private function parse_multitarget_expression(): Nodes
    {
        $targets = [];
        while (true) {
            $targets[] = $this->parser->parse_expression();
            if (!$this->parser->get_stream()->next_if(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }
        return new Nodes($targets);
    }
}