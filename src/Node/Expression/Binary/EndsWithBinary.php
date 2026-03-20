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
use Twig\Node\Expression\Return_Bool_Interface;
class Ends_With_Binary extends Abstract_Binary implements Return_Bool_Interface
{
    public function compile(Compiler $compiler): void
    {
        $left = $compiler->get_var_name();
        $right = $compiler->get_var_name();
        $compiler->raw(\sprintf('(is_string($%s = ', $left))->subcompile($this->get_node('left'))->raw(\sprintf(') && is_string($%s = ', $right))->subcompile($this->get_node('right'))->raw(\sprintf(') && str_ends_with($%1$s, $%2$s))', $left, $right));
    }
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('');
    }
}