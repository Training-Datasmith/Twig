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

trait Support_Defined_Test_Trait
{
    private bool $defined_test = false;
    public function enable_defined_test(): void
    {
        $this->defined_test = true;
    }
    public function is_defined_test_enabled(): bool
    {
        return $this->defined_test;
    }
}