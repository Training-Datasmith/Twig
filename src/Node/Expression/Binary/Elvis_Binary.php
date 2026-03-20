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
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Operator_Escape_Interface;
use Twig\Node\Node;
final class Elvis_Binary extends Abstract_Binary implements Operator_Escape_Interface
{
    /**
     * @param AbstractExpression $left
     * @param AbstractExpression $right
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        parent::__construct($left, $right, $lineno);
        $this->set_node('test', clone $left);
        $left->set_attribute('always_defined', true);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('((')->subcompile($this->get_node('test'))->raw(') ? (')->subcompile($this->get_node('left'))->raw(') : (')->subcompile($this->get_node('right'))->raw('))');
    }
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('?:');
    }
    public function get_operand_names_to_escape(): array
    {
        return ['left', 'right'];
    }
}