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
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Nodes;
use Twig\Token;
/**
 * Imports blocks defined in another template into the current template.
 *
 *    {% extends "base.html" %}
 *
 *    {% use "blocks.html" %}
 *
 *    {% block title %}{% endblock %}
 *    {% block content %}{% endblock %}
 *
 * @see https://twig.symfony.com/doc/templates.html#horizontal-reuse for details.
 *
 * @internal
 */
final class Use_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Empty_Node
    {
        $template = $this->parser->parse_expression();
        $stream = $this->parser->get_stream();
        if (!$template instanceof Constant_Expression) {
            throw new Syntax_Error('The template references in a "use" statement must be a string.', $stream->get_current()->get_line(), $stream->get_source_context());
        }
        $targets = [];
        if ($stream->next_if('with')) {
            while (true) {
                $name = $stream->expect(Token::NAME_TYPE)->get_value();
                $alias = $name;
                if ($stream->next_if('as')) {
                    $alias = $stream->expect(Token::NAME_TYPE)->get_value();
                }
                $targets[$name] = new Constant_Expression($alias, -1);
                if (!$stream->next_if(Token::PUNCTUATION_TYPE, ',')) {
                    break;
                }
            }
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        $this->parser->add_trait(new Nodes(['template' => $template, 'targets' => new Nodes($targets)]));
        return new Empty_Node($token->get_line());
    }
    public function get_tag(): string
    {
        return 'use';
    }
}