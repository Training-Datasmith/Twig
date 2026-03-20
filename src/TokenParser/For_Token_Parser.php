<?php

declare (strict_types=1);
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Twig\Token_Parser;

use Twig\Node\Expression\Variable\Assign_Context_Variable;
use Twig\Node\For_Else_Node;
use Twig\Node\For_Node;
use Twig\Token;
/**
 * Loops over each item of a sequence.
 *
 *   <ul>
 *    {% for user in users %}
 *      <li>{{ user.username|e }}</li>
 *    {% endfor %}
 *   </ul>
 *
 * @internal
 */
final class For_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\For_Node
    {
        $lineno = $token->get_line();
        $stream = $this->parser->get_stream();
        $targets = $this->parse_assignment_expression();
        $stream->expect(Token::OPERATOR_TYPE, 'in');
        $seq = $this->parser->parse_expression();
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decide_for_fork(...));
        if ('else' == $stream->next()->get_value()) {
            $else_lineno = $stream->get_current()->get_line();
            $stream->expect(Token::BLOCK_END_TYPE);
            $else = new For_Else_Node($this->parser->subparse($this->decide_for_end(...), true), $else_lineno);
        } else {
            $else = null;
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        if (\count($targets) > 1) {
            $key_target = $targets->get_node('0');
            $key_target = new Assign_Context_Variable($key_target->get_attribute('name'), $key_target->get_template_line());
            $value_target = $targets->get_node('1');
        } else {
            $key_target = new Assign_Context_Variable('_key', $lineno);
            $value_target = $targets->get_node('0');
        }
        $value_target = new Assign_Context_Variable($value_target->get_attribute('name'), $value_target->get_template_line());
        return new For_Node($key_target, $value_target, $seq, null, $body, $else, $lineno);
    }
    public function decide_for_fork(Token $token): bool
    {
        return $token->test(['else', 'endfor']);
    }
    public function decide_for_end(Token $token): bool
    {
        return $token->test('endfor');
    }
    public function get_tag(): string
    {
        return 'for';
    }
}