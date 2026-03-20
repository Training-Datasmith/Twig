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
 * Interface for node visitor classes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface Node_Visitor_Interface
{
    /**
     * Called before child nodes are visited.
     *
     * @return Node The modified node
     */
    public function enter_node(Node $node, Environment $env): Node;
    /**
     * Called after child nodes are visited.
     *
     * @return Node|null The modified node or null if the node must be removed
     */
    public function leave_node(Node $node, Environment $env): ?Node;
    /**
     * Returns the priority for this visitor.
     *
     * Priority should be between -10 and 10 (0 is the default).
     *
     * @return int The priority level
     */
    public function get_priority();
}