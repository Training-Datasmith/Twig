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
namespace Twig\Node\Expression;

use Twig\Compiler;
use Twig\Node\Expression\Variable\Assign_Context_Variable;
class List_Expression extends Abstract_Expression
{
    /**
     * @param array<AssignContextVariable> $items
     */
    public function __construct(array $items, int $lineno)
    {
        parent::__construct($items, [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        foreach ($this as $i => $name) {
            if ($i) {
                $compiler->raw(', ');
            }
            $compiler->raw('$__')->raw($name->get_attribute('name'))->raw('__');
        }
    }
}