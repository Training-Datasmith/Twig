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
namespace Twig;

use Twig\Node\Node;
use Twig\Node_Visitor\Node_Visitor_Interface;
/**
 * A node traverser.
 *
 * It visits all nodes and their children and calls the given visitor for each.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Node_Traverser
{
    private array $visitors = [];
    /**
     * @param NodeVisitorInterface[] $visitors
     */
    public function __construct(private readonly Environment $env, array $visitors = [])
    {
        foreach ($visitors as $visitor) {
            $this->add_visitor($visitor);
        }
    }
    public function add_visitor(Node_Visitor_Interface $visitor): void
    {
        $this->visitors[$visitor->get_priority()][] = $visitor;
    }
    /**
     * Traverses a node and calls the registered visitors.
     */
    public function traverse(Node $node): Node
    {
        ksort($this->visitors);
        foreach ($this->visitors as $visitors) {
            foreach ($visitors as $visitor) {
                $node = $this->traverse_for_visitor($visitor, $node);
            }
        }
        return $node;
    }
    private function traverse_for_visitor(Node_Visitor_Interface $visitor, Node $node): ?Node
    {
        $node = $visitor->enter_node($node, $this->env);
        foreach ($node as $k => $n) {
            if (null !== $m = $this->traverse_for_visitor($visitor, $n)) {
                if ($m !== $n) {
                    $node->set_node($k, $m);
                }
            } else {
                $node->remove_node($k);
            }
        }
        return $visitor->leave_node($node, $this->env);
    }
}