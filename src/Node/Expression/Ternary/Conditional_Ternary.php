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
namespace Twig\Node\Expression\Ternary;

use Twig\Compiler;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Operator_Escape_Interface;
use Twig\Node\Expression\Return_Primitive_Type_Interface;
use Twig\Node\Expression\Test\True_Test;
use Twig\Twig_Test;
final class Conditional_Ternary extends Abstract_Expression implements Operator_Escape_Interface
{
    public function __construct(Abstract_Expression $test, Abstract_Expression $left, Abstract_Expression $right, int $lineno)
    {
        if (!$test instanceof Return_Primitive_Type_Interface) {
            $test = new True_Test($test, new Twig_Test('true'), null, $test->get_template_line());
        }
        parent::__construct(['test' => $test, 'left' => $left, 'right' => $right], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('((')->subcompile($this->get_node('test'))->raw(') ? (')->subcompile($this->get_node('left'))->raw(') : (')->subcompile($this->get_node('right'))->raw('))');
    }
    public function get_operand_names_to_escape(): array
    {
        return ['left', 'right'];
    }
}