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
use Twig\Node\Empty_Node;
use Twig\Node\Nodes;
use Twig\Token;
/**
 * @internal
 */
final class Guard_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Nodes
    {
        $stream = $this->parser->get_stream();
        $type_token = $stream->expect(Token::NAME_TYPE);
        if (!\in_array($type_token->get_value(), ['function', 'filter', 'test'], true)) {
            throw new Syntax_Error(\sprintf('Supported guard types are function, filter and test, "%s" given.', $type_token->get_value()), $type_token->get_line(), $stream->get_source_context());
        }
        $method = 'get' . $type_token->get_value();
        $name_token = $stream->expect(Token::NAME_TYPE);
        $name = $name_token->get_value();
        if ('test' === $type_token->get_value() && $stream->test(Token::NAME_TYPE)) {
            // try 2-words tests
            $name .= ' ' . $stream->get_current()->get_value();
            $stream->next();
        }
        try {
            $exists = null !== $this->parser->get_environment()->{$method}($name);
        } catch (Syntax_Error) {
            $exists = false;
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        if ($exists) {
            $body = $this->parser->subparse($this->decide_guard_fork(...));
        } else {
            $body = new Empty_Node();
            $this->parser->subparse_ignore_unknown_twig_callables($this->decide_guard_fork(...));
        }
        $else = new Empty_Node();
        if ('else' === $stream->next()->get_value()) {
            $stream->expect(Token::BLOCK_END_TYPE);
            $else = $this->parser->subparse($this->decide_guard_end(...), true);
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        return new Nodes([$exists ? $body : $else]);
    }
    public function decide_guard_fork(Token $token): bool
    {
        return $token->test(['else', 'endguard']);
    }
    public function decide_guard_end(Token $token): bool
    {
        return $token->test(['endguard']);
    }
    public function get_tag(): string
    {
        return 'guard';
    }
}