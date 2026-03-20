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

use Twig\Expression_Parser\Infix\Filter_Expression_Parser;
use Twig\Node\Expression\Variable\Local_Variable;
use Twig\Node\Nodes;
use Twig\Node\Print_Node;
use Twig\Node\Set_Node;
use Twig\Token;
/**
 * Applies filters on a section of a template.
 *
 *   {% apply upper %}
 *      This text becomes uppercase
 *   {% endapply %}
 *
 * @internal
 */
final class Apply_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Nodes
    {
        $lineno = $token->get_line();
        $ref = new Local_Variable(null, $lineno);
        $filter = $ref;
        $op = $this->parser->get_environment()->get_expression_parsers()->get_by_class(Filter_Expression_Parser::class);
        while (true) {
            $filter = $op->parse($this->parser, $filter, $this->parser->get_current_token());
            if (!$this->parser->get_stream()->test(Token::OPERATOR_TYPE, '|')) {
                break;
            }
            $this->parser->get_stream()->next();
        }
        $this->parser->get_stream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decide_apply_end(...), true);
        $this->parser->get_stream()->expect(Token::BLOCK_END_TYPE);
        return new Nodes([new Set_Node(true, $ref, $body, $lineno), new Print_Node($filter, $lineno)], $lineno);
    }
    public function decide_apply_end(Token $token): bool
    {
        return $token->test('endapply');
    }
    public function get_tag(): string
    {
        return 'apply';
    }
}