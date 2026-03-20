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
namespace Twig\Node_Visitor;

use Twig\Environment;
use Twig\Node\Node;
/**
 * Used to make node visitors compatible with Twig 1.x and 2.x.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since Twig 3.9 (to be removed in 4.0)
 */
abstract class Abstract_Node_Visitor implements Node_Visitor_Interface
{
    final public function enter_node(Node $node, Environment $env): Node
    {
        return $this->do_enter_node($node, $env);
    }
    final public function leave_node(Node $node, Environment $env): ?Node
    {
        return $this->do_leave_node($node, $env);
    }
    /**
     * Called before child nodes are visited.
     *
     * @return Node The modified node
     */
    abstract protected function do_enter_node(Node $node, Environment $env);
    /**
     * Called after child nodes are visited.
     *
     * @return Node|null The modified node or null if the node must be removed
     */
    abstract protected function do_leave_node(Node $node, Environment $env);
}