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
namespace Twig\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
use Twig\Node\Expression\Abstract_Expression;
/**
 * Represents a node that outputs an expression.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Print_Node extends Node implements Node_Output_Interface
{
    public function __construct(Abstract_Expression $expr, int $lineno)
    {
        parent::__construct(['expr' => $expr], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        /** @var AbstractExpression */
        $expr = $this->get_node('expr');
        $compiler->add_debug_info($this)->write($expr->is_generator() ? 'yield from ' : 'yield ')->subcompile($expr)->raw(";\n");
    }
}