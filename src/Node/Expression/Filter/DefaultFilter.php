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
namespace Twig\Node\Expression\Filter;

use Twig\Attribute\First_Class_Twig_Callable_Ready;
use Twig\Compiler;
use Twig\Extension\Core_Extension;
use Twig\Node\Empty_Node;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Filter_Expression;
use Twig\Node\Expression\Get_Attr_Expression;
use Twig\Node\Expression\Ternary\Conditional_Ternary;
use Twig\Node\Expression\Test\Defined_Test;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\Node;
use Twig\Twig_Filter;
use Twig\Twig_Test;
/**
 * Returns the value or the default value when it is undefined or empty.
 *
 *  {{ var.foo|default('foo item on var is not defined') }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Default_Filter extends Filter_Expression
{
    /**
     * @param AbstractExpression $node
     */
    #[First_Class_Twig_Callable_Ready]
    public function __construct(Node $node, Twig_Filter|Constant_Expression $filter, Node $arguments, int $lineno)
    {
        if (!$node instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance to the "node" argument of "%s" is deprecated ("%s" given).', Abstract_Expression::class, static::class, $node::class);
        }
        if ($filter instanceof Twig_Filter) {
            $name = $filter->get_name();
            $default = new Filter_Expression($node, $filter, $arguments, $node->get_template_line());
        } else {
            $name = $filter->get_attribute('value');
            $default = new Filter_Expression($node, new Twig_Filter('default', Core_Extension::default(...)), $arguments, $node->get_template_line());
        }
        if ('default' === $name && ($node instanceof Context_Variable || $node instanceof Get_Attr_Expression)) {
            $test = new Defined_Test(clone $node, new Twig_Test('defined'), new Empty_Node(), $node->get_template_line());
            $false = \count($arguments) ? $arguments->get_node('0') : new Constant_Expression('', $node->get_template_line());
            $node = new Conditional_Ternary($test, $default, $false, $node->get_template_line());
        } else {
            $node = $default;
        }
        parent::__construct($node, $filter, $arguments, $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->subcompile($this->get_node('node'));
    }
}