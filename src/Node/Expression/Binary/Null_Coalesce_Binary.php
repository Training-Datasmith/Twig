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
namespace Twig\Node\Expression\Binary;

use Twig\Compiler;
use Twig\Node\Empty_Node;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Block_Reference_Expression;
use Twig\Node\Expression\Operator_Escape_Interface;
use Twig\Node\Expression\Test\Defined_Test;
use Twig\Node\Expression\Test\Null_Test;
use Twig\Node\Expression\Unary\Not_Unary;
use Twig\Node\Node;
use Twig\Twig_Test;
final class Null_Coalesce_Binary extends Abstract_Binary implements Operator_Escape_Interface
{
    /**
     * @param AbstractExpression $left
     * @param AbstractExpression $right
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        parent::__construct($left, $right, $lineno);
        $test = new Defined_Test(clone $left, new Twig_Test('defined'), new Empty_Node(), $left->get_template_line());
        // for "block()", we don't need the null test as the return value is always a string
        if (!$left instanceof Block_Reference_Expression) {
            $test = new And_Binary($test, new Not_Unary(new Null_Test($left, new Twig_Test('null'), new Empty_Node(), $left->get_template_line()), $left->get_template_line()), $left->get_template_line());
        }
        $left->set_attribute('always_defined', true);
        $this->set_node('test', $test);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('((')->subcompile($this->get_node('test'))->raw(') ? (')->subcompile($this->get_node('left'))->raw(') : (')->subcompile($this->get_node('right'))->raw('))');
    }
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('??');
    }
    public function get_operand_names_to_escape(): array
    {
        return ['left', 'right'];
    }
}