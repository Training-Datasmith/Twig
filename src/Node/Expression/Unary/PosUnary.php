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
namespace Twig\Node\Expression\Unary;

use Twig\Compiler;
class Pos_Unary extends Abstract_Unary
{
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('+');
    }
}