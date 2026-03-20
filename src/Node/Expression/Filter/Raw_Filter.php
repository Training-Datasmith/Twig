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
use Twig\Node\Empty_Node;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Filter_Expression;
use Twig\Node\Node;
use Twig\Twig_Filter;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Raw_Filter extends Filter_Expression
{
    /**
     * @param AbstractExpression $node
     */
    #[First_Class_Twig_Callable_Ready]
    public function __construct(Node $node, Twig_Filter|Constant_Expression|null $filter = null, ?Node $arguments = null, int $lineno = 0)
    {
        if (!$node instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance to the "node" argument of "%s" is deprecated ("%s" given).', Abstract_Expression::class, static::class, $node::class);
        }
        parent::__construct($node, $filter ?: new Twig_Filter('raw', null, ['is_safe' => ['all']]), $arguments ?: new Empty_Node(), $lineno ?: $node->get_template_line());
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->subcompile($this->get_node('node'));
    }
}