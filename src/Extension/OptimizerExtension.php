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

use Twig\Node_Visitor\Optimizer_Node_Visitor;
final class Optimizer_Extension extends Abstract_Extension
{
    public function __construct(private readonly int $optimizers = -1)
    {
    }
    public function get_node_visitors(): array
    {
        return [new Optimizer_Node_Visitor($this->optimizers)];
    }
}