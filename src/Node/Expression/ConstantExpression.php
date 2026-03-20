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
namespace Twig\Node\Expression;

use Twig\Compiler;
/**
 * @final
 */
class Constant_Expression extends Abstract_Expression implements Support_Defined_Test_Interface, Return_Primitive_Type_Interface
{
    use Support_Defined_Test_Trait;
    public function __construct($value, int $lineno)
    {
        parent::__construct([], ['value' => $value], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->repr($this->defined_test ? true : $this->get_attribute('value'));
    }
}