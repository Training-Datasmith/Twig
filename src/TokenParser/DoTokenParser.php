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

use Twig\Node\Do_Node;
use Twig\Token;
/**
 * Evaluates an expression, discarding the returned value.
 *
 * @internal
 */
final class Do_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Do_Node
    {
        $expr = $this->parser->parse_expression();
        $this->parser->get_stream()->expect(Token::BLOCK_END_TYPE);
        return new Do_Node($expr, $token->get_line());
    }
    public function get_tag(): string
    {
        return 'do';
    }
}