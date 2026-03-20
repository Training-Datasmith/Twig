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
namespace Twig;

use Twig\Error\Syntax_Error;
use Twig\Expression_Parser\Infix\Dot_Expression_Parser;
use Twig\Expression_Parser\Infix\Filter_Expression_Parser;
use Twig\Expression_Parser\Infix\Square_Bracket_Expression_Parser;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Unary\Neg_Unary;
use Twig\Node\Expression\Unary\Pos_Unary;
use Twig\Node\Expression\Unary\Spread_Unary;
use Twig\Node\Expression\Variable\Assign_Context_Variable;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\Node;
use Twig\Node\Nodes;
/**
 * Parses expressions.
 *
 * This parser implements a "Precedence climbing" algorithm.
 *
 * @see https://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
 * @see https://en.wikipedia.org/wiki/Operator-precedence_parser
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since Twig 3.21
 */
class Expression_Parser
{
    /**
     * @deprecated since Twig 3.21
     */
    public const OPERATOR_LEFT = 1;
    /**
     * @deprecated since Twig 3.21
     */
    public const OPERATOR_RIGHT = 2;
    public function __construct(private readonly Parser $parser)
    {
        trigger_deprecation('twig/twig', '3.21', 'Class "%s" is deprecated, use "Parser::parseExpression()" instead.', self::class);
    }
    public function parse_expression($precedence = 0): \Twig\Node\Expression\Abstract_Expression
    {
        if (\func_num_args() > 1) {
            trigger_deprecation('twig/twig', '3.15', 'Passing a second argument ($allowArrow) to "%s()" is deprecated.', __METHOD__);
        }
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated, use "Parser::parseExpression()" instead.', __METHOD__);
        return $this->parser->parse_expression((int) $precedence);
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function parse_primary_expression()
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated.', __METHOD__);
        return $this->parse_expression();
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function parse_string_expression()
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated.', __METHOD__);
        return $this->parse_expression();
    }
    /**
     * @deprecated since Twig 3.11, use parseExpression() instead
     */
    public function parse_array_expression()
    {
        trigger_deprecation('twig/twig', '3.11', 'Calling "%s()" is deprecated, use "parseExpression()" instead.', __METHOD__);
        return $this->parse_expression();
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function parse_sequence_expression()
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated.', __METHOD__);
        return $this->parse_expression();
    }
    /**
     * @deprecated since Twig 3.11, use parseExpression() instead
     */
    public function parse_hash_expression()
    {
        trigger_deprecation('twig/twig', '3.11', 'Calling "%s()" is deprecated, use "parseExpression()" instead.', __METHOD__);
        return $this->parse_expression();
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function parse_mapping_expression()
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated.', __METHOD__);
        return $this->parse_expression();
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function parse_postfix_expression($node)
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated.', __METHOD__);
        while (true) {
            $token = $this->parser->get_current_token();
            if ($token->test(Token::PUNCTUATION_TYPE)) {
                if ('.' == $token->get_value() || '[' == $token->get_value()) {
                    $node = $this->parse_subscript_expression($node);
                } elseif ('|' == $token->get_value()) {
                    $node = $this->parse_filter_expression($node);
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        return $node;
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function parse_subscript_expression($node)
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated.', __METHOD__);
        $parsers = new \ReflectionProperty($this->parser, 'parsers');
        if ('.' === $this->parser->get_stream()->next()->get_value()) {
            return $parsers->get_value($this->parser)->get_by_class(Dot_Expression_Parser::class)->parse($this->parser, $node, $this->parser->get_current_token());
        }
        return $parsers->get_value($this->parser)->get_by_class(Square_Bracket_Expression_Parser::class)->parse($this->parser, $node, $this->parser->get_current_token());
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function parse_filter_expression($node)
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated.', __METHOD__);
        $this->parser->get_stream()->next();
        return $this->parse_filter_expression_raw($node);
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function parse_filter_expression_raw($node)
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated.', __METHOD__);
        $parsers = new \ReflectionProperty($this->parser, 'parsers');
        $op = $parsers->get_value($this->parser)->get_by_class(Filter_Expression_Parser::class);
        while (true) {
            $node = $op->parse($this->parser, $node, $this->parser->get_current_token());
            if (!$this->parser->get_stream()->test(Token::OPERATOR_TYPE, '|')) {
                break;
            }
            $this->parser->get_stream()->next();
        }
        return $node;
    }
    /**
     * Parses arguments.
     *
     * @return Node
     *
     * @throws SyntaxError
     *
     * @deprecated since Twig 3.19 Use Twig\ExpressionParser\Infix\ArgumentsTrait::parseNamedArguments() instead
     */
    public function parse_arguments(): \Twig\Node\Nodes
    {
        trigger_deprecation('twig/twig', '3.19', \sprintf('The "%s()" method is deprecated, use "Twig\ExpressionParser\Infix\ArgumentsTrait::parseNamedArguments()" instead.', __METHOD__));
        $parse_primary_expression = new \ReflectionMethod($this->parser, 'parsePrimaryExpression');
        $named_arguments = false;
        $definition = false;
        if (\func_num_args() > 1) {
            $definition = func_get_arg(1);
        }
        if (\func_num_args() > 0) {
            trigger_deprecation('twig/twig', '3.15', 'Passing arguments to "%s()" is deprecated.', __METHOD__);
            $named_arguments = func_get_arg(0);
        }
        $args = [];
        $stream = $this->parser->get_stream();
        $stream->expect(Token::OPERATOR_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');
        $has_spread = false;
        while (!$stream->test(Token::PUNCTUATION_TYPE, ')')) {
            if ($args) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');
                // if the comma above was a trailing comma, early exit the argument parse loop
                if ($stream->test(Token::PUNCTUATION_TYPE, ')')) {
                    break;
                }
            }
            if ($definition) {
                $token = $stream->expect(Token::NAME_TYPE, null, 'An argument must be a name');
                $value = new Context_Variable($token->get_value(), $this->parser->get_current_token()->get_line());
            } else if ($stream->next_if(Token::SPREAD_TYPE)) {
                $has_spread = true;
                $value = new Spread_Unary($this->parse_expression(), $stream->get_current()->get_line());
            } elseif ($has_spread) {
                throw new Syntax_Error('Normal arguments must be placed before argument unpacking.', $stream->get_current()->get_line(), $stream->get_source_context());
            } else {
                $value = $this->parse_expression();
            }
            $name = null;
            if ($named_arguments && (($token = $stream->next_if(Token::OPERATOR_TYPE, '=')) || !$definition && $token = $stream->next_if(Token::PUNCTUATION_TYPE, ':'))) {
                if (!$value instanceof Context_Variable) {
                    throw new Syntax_Error(\sprintf('A parameter name must be a string, "%s" given.', $value::class), $token->get_line(), $stream->get_source_context());
                }
                $name = $value->get_attribute('name');
                if ($definition) {
                    $value = $parse_primary_expression->invoke($this->parser);
                    if (!$this->check_constant_expression($value)) {
                        throw new Syntax_Error('A default value for an argument must be a constant (a boolean, a string, a number, a sequence, or a mapping).', $token->get_line(), $stream->get_source_context());
                    }
                } else {
                    $value = $this->parse_expression();
                }
            }
            if ($definition) {
                if (null === $name) {
                    $name = $value->get_attribute('name');
                    $value = new Constant_Expression(null, $this->parser->get_current_token()->get_line());
                    $value->set_attribute('is_implicit', true);
                }
                $args[$name] = $value;
            } else if (null === $name) {
                $args[] = $value;
            } else {
                $args[$name] = $value;
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');
        return new Nodes($args);
    }
    /**
     * @deprecated since Twig 3.21, use "AbstractTokenParser::parseAssignmentExpression()" instead
     */
    public function parse_assignment_expression(): \Twig\Node\Nodes
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated, use "AbstractTokenParser::parseAssignmentExpression()" instead.', __METHOD__);
        $stream = $this->parser->get_stream();
        $targets = [];
        while (true) {
            $token = $this->parser->get_current_token();
            if ($stream->test(Token::OPERATOR_TYPE) && preg_match(Lexer::REGEX_NAME, (string) $token->get_value())) {
                // in this context, string operators are variable names
                $this->parser->get_stream()->next();
            } else {
                $stream->expect(Token::NAME_TYPE, null, 'Only variables can be assigned to');
            }
            $targets[] = new Assign_Context_Variable($token->get_value(), $token->get_line());
            if (!$stream->next_if(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }
        return new Nodes($targets);
    }
    /**
     * @deprecated since Twig 3.21
     */
    public function parse_multitarget_expression(): \Twig\Node\Nodes
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s()" method is deprecated.', __METHOD__);
        $targets = [];
        while (true) {
            $targets[] = $this->parse_expression();
            if (!$this->parser->get_stream()->next_if(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }
        return new Nodes($targets);
    }
    // checks that the node only contains "constant" elements
    // to be removed in 4.0
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
    /**
     * @deprecated since Twig 3.19 Use Twig\ExpressionParser\Infix\ArgumentsTrait::parseNamedArguments() instead
     */
    public function parse_only_arguments()
    {
        trigger_deprecation('twig/twig', '3.19', \sprintf('The "%s()" method is deprecated, use "Twig\ExpressionParser\Infix\ArgumentsTrait::parseNamedArguments()" instead.', __METHOD__));
        return $this->parse_arguments();
    }
}