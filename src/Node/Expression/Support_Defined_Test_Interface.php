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

/**
 * Interface implemented by expressions that support the defined test.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface Support_Defined_Test_Interface
{
    public function enable_defined_test(): void;
    public function is_defined_test_enabled(): bool;
}