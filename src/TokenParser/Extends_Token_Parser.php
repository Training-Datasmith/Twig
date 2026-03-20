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
use Twig\Node\Empty_Node;
use Twig\Token;
/**
 * Extends a template by another one.
 *
 *  {% extends "base.html" %}
 *
 * @internal
 */
final class Extends_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Empty_Node
    {
        $stream = $this->parser->get_stream();
        if ($this->parser->peek_block_stack()) {
            throw new Syntax_Error('Cannot use "extend" in a block.', $token->get_line(), $stream->get_source_context());
        }
        if (!$this->parser->is_main_scope()) {
            throw new Syntax_Error('Cannot use "extend" in a macro.', $token->get_line(), $stream->get_source_context());
        }
        $this->parser->set_parent($this->parser->parse_expression());
        $stream->expect(Token::BLOCK_END_TYPE);
        return new Empty_Node($token->get_line());
    }
    public function get_tag(): string
    {
        return 'extends';
    }
}