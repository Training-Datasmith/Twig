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
class Not_In_Binary extends Abstract_Binary implements Return_Bool_Interface
{
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('!CoreExtension::inFilter(')->subcompile($this->get_node('left'))->raw(', ')->subcompile($this->get_node('right'))->raw(')');
    }
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('not in');
    }
}