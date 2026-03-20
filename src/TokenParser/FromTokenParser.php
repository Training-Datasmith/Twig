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

use Twig\Node\Expression\Variable\Assign_Context_Variable;
use Twig\Node\Expression\Variable\Assign_Template_Variable;
use Twig\Node\Expression\Variable\Template_Variable;
use Twig\Node\Import_Node;
use Twig\Token;
/**
 * Imports macros.
 *
 *   {% from 'forms.html.twig' import forms %}
 *
 * @internal
 */
final class From_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Import_Node
    {
        $macro = $this->parser->parse_expression();
        $stream = $this->parser->get_stream();
        $stream->expect(Token::NAME_TYPE, 'import');
        $targets = [];
        while (true) {
            $name = $stream->expect(Token::NAME_TYPE)->get_value();
            if ($stream->next_if('as')) {
                $alias = new Assign_Context_Variable($stream->expect(Token::NAME_TYPE)->get_value(), $token->get_line());
            } else {
                $alias = new Assign_Context_Variable($name, $token->get_line());
            }
            $targets[$name] = $alias;
            if (!$stream->next_if(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        $internal_ref = new Assign_Template_Variable(new Template_Variable(null, $token->get_line()), $this->parser->is_main_scope());
        $node = new Import_Node($macro, $internal_ref, $token->get_line());
        foreach ($targets as $name => $alias) {
            $this->parser->add_imported_symbol('function', $alias->get_attribute('name'), 'macro_' . $name, $internal_ref);
        }
        return $node;
    }
    public function get_tag(): string
    {
        return 'from';
    }
}