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
 * Represents an else node in a for loop.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class For_Else_Node extends Node
{
    public function __construct(Node $body, int $lineno)
    {
        parent::__construct(['body' => $body], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this)->write("if (!\$context['_iterated']) {\n")->indent()->subcompile($this->get_node('body'))->outdent()->write("}\n");
    }
}