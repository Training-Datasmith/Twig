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

use Twig\Node\Embed_Node;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Token;
/**
 * Embeds a template.
 *
 * @internal
 */
final class Embed_Token_Parser extends Include_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Embed_Node
    {
        $stream = $this->parser->get_stream();
        $parent = $this->parser->parse_expression();
        [$variables, $only, $ignore_missing] = $this->parse_arguments();
        $parent_token = $fake_parent_token = new Token(Token::STRING_TYPE, '__parent__', $token->get_line());
        if ($parent instanceof Constant_Expression) {
            $parent_token = new Token(Token::STRING_TYPE, $parent->get_attribute('value'), $token->get_line());
        } elseif ($parent instanceof Context_Variable) {
            $parent_token = new Token(Token::NAME_TYPE, $parent->get_attribute('name'), $token->get_line());
        }
        // inject a fake parent to make the parent() function work
        $stream->inject_tokens([new Token(Token::BLOCK_START_TYPE, '', $token->get_line()), new Token(Token::NAME_TYPE, 'extends', $token->get_line()), $parent_token, new Token(Token::BLOCK_END_TYPE, '', $token->get_line())]);
        $module = $this->parser->parse($stream, $this->decide_block_end(...), true);
        // override the parent with the correct one
        if ($fake_parent_token === $parent_token) {
            $module->set_node('parent', $parent);
        }
        $this->parser->embed_template($module);
        $stream->expect(Token::BLOCK_END_TYPE);
        return new Embed_Node($module->get_template_name(), $module->get_attribute('index'), $variables, $only, $ignore_missing, $token->get_line());
    }
    public function decide_block_end(Token $token): bool
    {
        return $token->test('endembed');
    }
    public function get_tag(): string
    {
        return 'embed';
    }
}