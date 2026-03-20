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
namespace Twig\Node;

use Twig\Attribute\Yield_Ready;
/**
 * Represents an empty node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
final class Empty_Node extends Node
{
    public function __construct(int $lineno = 0)
    {
        parent::__construct([], [], $lineno);
    }
    public function set_node(string|int $name, Node $node): void
    {
        throw new \LogicException('EmptyNode cannot have children.');
    }
}