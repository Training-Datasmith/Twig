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
use Twig\Node\Body_Node;
use Twig\Node\Empty_Node;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Unary\Neg_Unary;
use Twig\Node\Expression\Unary\Pos_Unary;
use Twig\Node\Expression\Variable\Local_Variable;
use Twig\Node\Macro_Node;
use Twig\Node\Node;
use Twig\Token;
/**
 * Defines a macro.
 *
 *   {% macro input(name, value, type, size) %}
 *      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
 *   {% endmacro %}
 *
 * @internal
 */
final class Macro_Token_Parser extends Abstract_Token_Parser
{
    public function parse(Token $token): \Twig\Node\Empty_Node
    {
        $lineno = $token->get_line();
        $stream = $this->parser->get_stream();
        $name = $stream->expect(Token::NAME_TYPE)->get_value();
        $arguments = $this->parse_definition();
        $stream->expect(Token::BLOCK_END_TYPE);
        $this->parser->push_local_scope();
        $body = $this->parser->subparse($this->decide_block_end(...), true);
        if ($token = $stream->next_if(Token::NAME_TYPE)) {
            $value = $token->get_value();
            if ($value != $name) {
                throw new Syntax_Error(\sprintf('Expected endmacro for macro "%s" (but "%s" given).', $name, $value), $stream->get_current()->get_line(), $stream->get_source_context());
            }
        }
        $this->parser->pop_local_scope();
        $stream->expect(Token::BLOCK_END_TYPE);
        $this->parser->set_macro($name, new Macro_Node($name, new Body_Node([$body]), $arguments, $lineno));
        return new Empty_Node($lineno);
    }
    public function decide_block_end(Token $token): bool
    {
        return $token->test('endmacro');
    }
    public function get_tag(): string
    {
        return 'macro';
    }
    private function parse_definition(): Array_Expression
    {
        $arguments = new Array_Expression([], $this->parser->get_current_token()->get_line());
        $stream = $this->parser->get_stream();
        $stream->expect(Token::OPERATOR_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');
        while (!$stream->test(Token::PUNCTUATION_TYPE, ')')) {
            if (\count($arguments)) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');
                // if the comma above was a trailing comma, early exit the argument parse loop
                if ($stream->test(Token::PUNCTUATION_TYPE, ')')) {
                    break;
                }
            }
            $token = $stream->expect(Token::NAME_TYPE, null, 'An argument must be a name');
            $name = new Local_Variable($token->get_value(), $this->parser->get_current_token()->get_line());
            if ($token = $stream->next_if(Token::OPERATOR_TYPE, '=')) {
                $default = $this->parser->parse_expression();
            } else {
                $default = new Constant_Expression(null, $this->parser->get_current_token()->get_line());
                $default->set_attribute('is_implicit', true);
            }
            if (!$this->check_constant_expression($default)) {
                throw new Syntax_Error('A default value for an argument must be a constant (a boolean, a string, a number, a sequence, or a mapping).', $token->get_line(), $stream->get_source_context());
            }
            $arguments->add_element($default, $name);
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');
        return $arguments;
    }
    // checks that the node only contains "constant" elements
    private function check_constant_expression(Node $node): bool
    {
        if (!($node instanceof Constant_Expression || $node instanceof Array_Expression || $node instanceof Neg_Unary || $node instanceof Pos_Unary)) {
            return false;
        }
        foreach ($node as $n) {
            if (!$this->check_constant_expression($n)) {
                return false;
            }
        }
        return true;
    }
}