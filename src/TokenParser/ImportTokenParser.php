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

use Twig\Node\Expression\Variable\Assign_Template_Variable;
use Twig\Node\Expression\Variable\Template_Variable;
use Twig\Node\Import_Node;
use Twig\Token;
/**
 * Imports macros.
 *
 *   {% import 'forms.html.twig' as forms %}
 *
 * @internal
 */
final class Import_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Import_Node
    {
        $macro = $this->parser->parse_expression();
        $this->parser->get_stream()->expect(Token::NAME_TYPE, 'as');
        $name = $this->parser->get_stream()->expect(Token::NAME_TYPE)->get_value();
        $var = new Assign_Template_Variable(new Template_Variable($name, $token->get_line()), $this->parser->is_main_scope());
        $this->parser->get_stream()->expect(Token::BLOCK_END_TYPE);
        $this->parser->add_imported_symbol('template', $name);
        return new Import_Node($macro, $var, $token->get_line());
    }
    public function get_tag(): string
    {
        return 'import';
    }
}