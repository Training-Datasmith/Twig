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
use Twig\Node\If_Node;
use Twig\Node\Nodes;
use Twig\Token;
/**
 * Tests a condition.
 *
 *   {% if users %}
 *    <ul>
 *      {% for user in users %}
 *        <li>{{ user.username|e }}</li>
 *      {% endfor %}
 *    </ul>
 *   {% endif %}
 *
 * @internal
 */
final class If_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\If_Node
    {
        $lineno = $token->get_line();
        $expr = $this->parser->parse_expression();
        $stream = $this->parser->get_stream();
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decide_if_fork(...));
        $tests = [$expr, $body];
        $else = null;
        $end = false;
        while (!$end) {
            switch ($stream->next()->get_value()) {
                case 'else':
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $else = $this->parser->subparse($this->decide_if_end(...));
                    break;
                case 'elseif':
                    $expr = $this->parser->parse_expression();
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse($this->decide_if_fork(...));
                    $tests[] = $expr;
                    $tests[] = $body;
                    break;
                case 'endif':
                    $end = true;
                    break;
                default:
                    throw new Syntax_Error(\sprintf('Unexpected end of template. Twig was looking for the following tags "else", "elseif", or "endif" to close the "if" block started at line %d).', $lineno), $stream->get_current()->get_line(), $stream->get_source_context());
            }
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        return new If_Node(new Nodes($tests), $else, $lineno);
    }
    public function decide_if_fork(Token $token): bool
    {
        return $token->test(['elseif', 'else', 'endif']);
    }
    public function decide_if_end(Token $token): bool
    {
        return $token->test(['endif']);
    }
    public function get_tag(): string
    {
        return 'if';
    }
}