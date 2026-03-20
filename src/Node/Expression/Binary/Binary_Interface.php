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

use Twig\Node\Expression\Abstract_Expression;
/**
 * @internal
 */
interface Binary_Interface
{
    public function __construct(Abstract_Expression $left, Abstract_Expression $right, int $lineno);
}