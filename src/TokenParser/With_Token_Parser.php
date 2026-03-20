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

use Twig\Node\With_Node;
use Twig\Token;
/**
 * Creates a nested scope.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class With_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\With_Node
    {
        $stream = $this->parser->get_stream();
        $variables = null;
        $only = false;
        if (!$stream->test(Token::BLOCK_END_TYPE)) {
            $variables = $this->parser->parse_expression();
            $only = (bool) $stream->next_if(Token::NAME_TYPE, 'only');
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decide_with_end(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);
        return new With_Node($body, $variables, $only, $token->get_line());
    }
    public function decide_with_end(Token $token): bool
    {
        return $token->test('endwith');
    }
    public function get_tag(): string
    {
        return 'with';
    }
}