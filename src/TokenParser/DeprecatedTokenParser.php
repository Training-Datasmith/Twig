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
use Twig\Node\Deprecated_Node;
use Twig\Token;
/**
 * Deprecates a section of a template.
 *
 *    {% deprecated 'The "base.twig" template is deprecated, use "layout.twig" instead.' %}
 *    {% extends 'layout.html.twig' %}
 *
 *    {% deprecated 'The "base.twig" template is deprecated, use "layout.twig" instead.' package="foo/bar" version="1.1" %}
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 *
 * @internal
 */
final class Deprecated_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Deprecated_Node
    {
        $stream = $this->parser->get_stream();
        $expr = $this->parser->parse_expression();
        $node = new Deprecated_Node($expr, $token->get_line());
        while ($stream->test(Token::NAME_TYPE)) {
            $k = $stream->get_current()->get_value();
            $stream->next();
            $stream->expect(Token::OPERATOR_TYPE, '=');
            match ($k) {
                'package' => $node->set_node('package', $this->parser->parse_expression()),
                'version' => $node->set_node('version', $this->parser->parse_expression()),
                default => throw new Syntax_Error(\sprintf('Unknown "%s" option.', $k), $stream->get_current()->get_line(), $stream->get_source_context()),
            };
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        return $node;
    }
    public function get_tag(): string
    {
        return 'deprecated';
    }
}