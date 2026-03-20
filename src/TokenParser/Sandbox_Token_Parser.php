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
use Twig\Node\Include_Node;
use Twig\Node\Sandbox_Node;
use Twig\Node\Text_Node;
use Twig\Token;
/**
 * Marks a section of a template as untrusted code that must be evaluated in the sandbox mode.
 *
 *    {% sandbox %}
 *        {% include 'user.html.twig' %}
 *    {% endsandbox %}
 *
 * @see https://twig.symfony.com/doc/api.html#sandbox-extension for details
 *
 * @internal
 */
final class Sandbox_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Sandbox_Node
    {
        $stream = $this->parser->get_stream();
        trigger_deprecation('twig/twig', '3.15', \sprintf('The "sandbox" tag is deprecated in "%s" at line %d.', $stream->get_source_context()->get_name(), $token->get_line()));
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decide_block_end(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);
        // in a sandbox tag, only include tags are allowed
        if (!$body instanceof Include_Node) {
            foreach ($body as $node) {
                if ($node instanceof Text_Node && ctype_space((string) $node->get_attribute('data'))) {
                    continue;
                }
                if (!$node instanceof Include_Node) {
                    throw new Syntax_Error('Only "include" tags are allowed within a "sandbox" section.', $node->get_template_line(), $stream->get_source_context());
                }
            }
        }
        return new Sandbox_Node($body, $token->get_line());
    }
    public function decide_block_end(Token $token): bool
    {
        return $token->test('endsandbox');
    }
    public function get_tag(): string
    {
        return 'sandbox';
    }
}