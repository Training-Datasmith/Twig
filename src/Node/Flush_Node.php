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
/**
 * Represents a flush node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Flush_Node extends Node
{
    public function __construct(int $lineno)
    {
        parent::__construct([], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this);
        if ($compiler->get_environment()->use_yield()) {
            $compiler->write("yield '';\n");
        }
        $compiler->write("flush();\n");
    }
}