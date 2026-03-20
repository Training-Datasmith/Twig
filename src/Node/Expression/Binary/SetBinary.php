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
use Twig\Node\Expression\Variable\Assign_Context_Variable;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\Node;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Set_Binary extends Abstract_Binary
{
    /**
     * @param ContextVariable    $left
     * @param AbstractExpression $right
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        $name = $left->get_attribute('name');
        if (!\is_string($name)) {
            throw new \LogicException('The "name" attribute must be a string.');
        }
        $left = new Assign_Context_Variable($name, $left->get_template_line());
        parent::__construct($left, $right, $lineno);
    }
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('=');
    }
}