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
namespace Twig\Extension;

use Twig\Expression_Parser;
use Twig\Expression_Parser\Expression_Parser_Interface;
use Twig\Expression_Parser\Precedence_Change;
use Twig\Node\Expression\Binary\Abstract_Binary;
use Twig\Node\Expression\Unary\Abstract_Unary;
use Twig\Node_Visitor\Node_Visitor_Interface;
use Twig\Token_Parser\Token_Parser_Interface;
use Twig\Twig_Filter;
use Twig\Twig_Function;
use Twig\Twig_Test;
/**
 * Interface implemented by extension classes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @method array<ExpressionParserInterface> getExpressionParsers()
 */
interface Extension_Interface
{
    /**
     * Returns the token parser instances to add to the existing list.
     *
     * @return TokenParserInterface[]
     */
    public function get_token_parsers();
    /**
     * Returns the node visitor instances to add to the existing list.
     *
     * @return NodeVisitorInterface[]
     */
    public function get_node_visitors();
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function get_filters();
    /**
     * Returns a list of tests to add to the existing list.
     *
     * @return TwigTest[]
     */
    public function get_tests();
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function get_functions();
    /**
     * Returns a list of operators to add to the existing list.
     *
     * @return array<array>
     *
     * @psalm-return array{
     *     array<string, array{precedence: int, precedence_change?: PrecedenceChange, class: class-string<AbstractUnary>}>,
     *     array<string, array{precedence: int, precedence_change?: PrecedenceChange, class?: class-string<AbstractBinary>, associativity: ExpressionParser::OPERATOR_*}>
     * }
     */
    public function get_operators();
}