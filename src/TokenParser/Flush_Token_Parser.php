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

use Twig\Node\Flush_Node;
use Twig\Token;
/**
 * Flushes the output to the client.
 *
 * @see flush()
 *
 * @internal
 */
final class Flush_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Flush_Node
    {
        $this->parser->get_stream()->expect(Token::BLOCK_END_TYPE);
        return new Flush_Node($token->get_line());
    }
    public function get_tag(): string
    {
        return 'flush';
    }
}