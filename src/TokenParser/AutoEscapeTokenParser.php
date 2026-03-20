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
use Twig\Node\Auto_Escape_Node;
use Twig\Node\Expression\Constant_Expression;
use Twig\Token;
/**
 * Marks a section of a template to be escaped or not.
 *
 * @internal
 */
final class Auto_Escape_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Auto_Escape_Node
    {
        $lineno = $token->get_line();
        $stream = $this->parser->get_stream();
        if ($stream->test(Token::BLOCK_END_TYPE)) {
            $value = 'html';
        } else {
            $expr = $this->parser->parse_expression();
            if (!$expr instanceof Constant_Expression) {
                throw new Syntax_Error('An escaping strategy must be a string or false.', $stream->get_current()->get_line(), $stream->get_source_context());
            }
            $value = $expr->get_attribute('value');
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decide_block_end(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);
        return new Auto_Escape_Node($value, $body, $lineno);
    }
    public function decide_block_end(Token $token): bool
    {
        return $token->test('endautoescape');
    }
    public function get_tag(): string
    {
        return 'autoescape';
    }
}