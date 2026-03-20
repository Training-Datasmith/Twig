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
class Variadic_Expression extends Array_Expression
{
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('...');
        parent::compile($compiler);
    }
}