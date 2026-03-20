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
class Same_As_Binary extends Abstract_Binary implements Return_Bool_Interface
{
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('===');
    }
}