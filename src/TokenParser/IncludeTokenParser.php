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

use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Include_Node;
use Twig\Node\Node;
use Twig\Token;
/**
 * Includes a template.
 *
 *   {% include 'header.html.twig' %}
 *     Body
 *   {% include 'footer.html.twig' %}
 *
 * @internal
 */
class Include_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): Node
    {
        $expr = $this->parser->parse_expression();
        [$variables, $only, $ignore_missing] = $this->parse_arguments();
        return new Include_Node($expr, $variables, $only, $ignore_missing, $token->get_line());
    }
    /**
     * @return array{0: ?AbstractExpression, 1: bool, 2: bool}
     */
    protected function parse_arguments(): array
    {
        $stream = $this->parser->get_stream();
        $ignore_missing = false;
        if ($stream->next_if(Token::NAME_TYPE, 'ignore')) {
            $stream->expect(Token::NAME_TYPE, 'missing');
            $ignore_missing = true;
        }
        $variables = null;
        if ($stream->next_if(Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->parse_expression();
        }
        $only = false;
        if ($stream->next_if(Token::NAME_TYPE, 'only')) {
            $only = true;
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        return [$variables, $only, $ignore_missing];
    }
    public function get_tag(): string
    {
        return 'include';
    }
}