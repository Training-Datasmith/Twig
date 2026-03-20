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

use Twig\Error\Syntax_Error;
use Twig\Node\Block_Node;
use Twig\Node\Block_Reference_Node;
use Twig\Node\Empty_Node;
use Twig\Node\Nodes;
use Twig\Node\Print_Node;
use Twig\Token;
/**
 * Marks a section of a template as being reusable.
 *
 *  {% block head %}
 *    <link rel="stylesheet" href="style.css" />
 *    <title>{% block title %}{% endblock %} - My Webpage</title>
 *  {% endblock %}
 *
 * @internal
 */
final class Block_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Block_Reference_Node
    {
        $lineno = $token->get_line();
        $stream = $this->parser->get_stream();
        $name = $stream->expect(Token::NAME_TYPE)->get_value();
        $this->parser->set_block($name, $block = new Block_Node($name, new Empty_Node(), $lineno));
        $this->parser->push_local_scope();
        $this->parser->push_block_stack($name);
        if ($stream->next_if(Token::BLOCK_END_TYPE)) {
            $body = $this->parser->subparse($this->decide_block_end(...), true);
            if ($token = $stream->next_if(Token::NAME_TYPE)) {
                $value = $token->get_value();
                if ($value != $name) {
                    throw new Syntax_Error(\sprintf('Expected endblock for block "%s" (but "%s" given).', $name, $value), $stream->get_current()->get_line(), $stream->get_source_context());
                }
            }
        } else {
            $body = new Nodes([new Print_Node($this->parser->parse_expression(), $lineno)]);
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        $block->set_node('body', $body);
        $this->parser->pop_block_stack();
        $this->parser->pop_local_scope();
        return new Block_Reference_Node($name, $lineno);
    }
    public function decide_block_end(Token $token): bool
    {
        return $token->test('endblock');
    }
    public function get_tag(): string
    {
        return 'block';
    }
}