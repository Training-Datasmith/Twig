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
namespace Twig\Node\Expression\Variable;

use Twig\Compiler;
use Twig\Node\Expression\Temp_Name_Expression;
class Template_Variable extends Temp_Name_Expression
{
    public function get_name(Compiler $compiler): string
    {
        if (null === $this->get_attribute('name')) {
            $this->set_attribute('name', $compiler->get_var_name());
        }
        return $this->get_attribute('name');
    }
    public function compile(Compiler $compiler): void
    {
        $name = $this->get_name($compiler);
        if ('_self' === $name) {
            $compiler->raw('$this');
        } else {
            $compiler->raw('$macros[')->string($name)->raw(']');
        }
    }
}