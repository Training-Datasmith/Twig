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
/**
 * Represents a text node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Text_Node extends Node implements Node_Output_Interface
{
    public function __construct(string $data, int $lineno)
    {
        parent::__construct([], ['data' => $data], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this);
        $compiler->write('yield ')->string($this->get_attribute('data'))->raw(";\n");
    }
}