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
namespace Twig\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
use Twig\Node\Expression\Abstract_Expression;
/**
 * Represents a do node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Do_Node extends Node
{
    public function __construct(Abstract_Expression $expr, int $lineno)
    {
        parent::__construct(['expr' => $expr], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this)->write('')->subcompile($this->get_node('expr'))->raw(";\n");
    }
}