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
namespace Twig\Extension;

use Twig\Node_Visitor\Yield_Not_Ready_Node_Visitor;
/**
 * @internal to be removed in Twig 4
 */
final class Yield_Not_Ready_Extension extends Abstract_Extension
{
    public function __construct(private readonly bool $use_yield)
    {
    }
    public function get_node_visitors(): array
    {
        return [new Yield_Not_Ready_Node_Visitor($this->use_yield)];
    }
}