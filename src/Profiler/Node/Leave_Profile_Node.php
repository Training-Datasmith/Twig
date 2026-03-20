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
namespace Twig\Profiler\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
use Twig\Node\Node;
/**
 * Represents a profile leave node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Leave_Profile_Node extends Node
{
    public function __construct(string $var_name)
    {
        parent::__construct([], ['var_name' => $var_name]);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->write("\n")->write(\sprintf("\$%s->leave(\$%s);\n\n", $this->get_attribute('var_name'), $this->get_attribute('var_name') . '_prof'));
    }
}