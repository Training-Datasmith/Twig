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
use Twig\Node\Block_Reference_Node;
use Twig\Node\Expression\Block_Reference_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Function_Expression;
use Twig\Node\Expression\Get_Attr_Expression;
use Twig\Node\Expression\Parent_Expression;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\For_Node;
use Twig\Node\Include_Node;
use Twig\Node\Node;
use Twig\Node\Print_Node;
use Twig\Node\Text_Node;
/**
 * Tries to optimize the AST.
 *
 * This visitor is always the last registered one.
 *
 * You can configure which optimizations you want to activate via the
 * optimizer mode.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class Optimizer_Node_Visitor implements Node_Visitor_Interface
{
    public const OPTIMIZE_ALL = -1;
    public const OPTIMIZE_NONE = 0;
    public const OPTIMIZE_FOR = 2;
    public const OPTIMIZE_RAW_FILTER = 4;
    public const OPTIMIZE_TEXT_NODES = 8;
    private array $loops = [];
    private array $loops_targets = [];
    /**
     * @param int $optimizers The optimizer mode
     */
    public function __construct(private readonly int $optimizers = -1)
    {
        if ($optimizers > (self::OPTIMIZE_FOR | self::OPTIMIZE_RAW_FILTER | self::OPTIMIZE_TEXT_NODES)) {
            throw new \InvalidArgumentException(\sprintf('Optimizer mode "%s" is not valid.', $optimizers));
        }
        if (-1 !== $optimizers && self::OPTIMIZE_RAW_FILTER === (self::OPTIMIZE_RAW_FILTER & $optimizers)) {
            trigger_deprecation('twig/twig', '3.11', 'The "Twig\NodeVisitor\OptimizerNodeVisitor::OPTIMIZE_RAW_FILTER" option is deprecated and does nothing.');
        }
        if (-1 !== $optimizers && self::OPTIMIZE_TEXT_NODES === (self::OPTIMIZE_TEXT_NODES & $optimizers)) {
            trigger_deprecation('twig/twig', '3.12', 'The "Twig\NodeVisitor\OptimizerNodeVisitor::OPTIMIZE_TEXT_NODES" option is deprecated and does nothing.');
        }
    }
    public function enter_node(Node $node, Environment $env): Node
    {
        if (self::OPTIMIZE_FOR === (self::OPTIMIZE_FOR & $this->optimizers)) {
            $this->enter_optimize_for($node);
        }
        return $node;
    }
    public function leave_node(Node $node, Environment $env): \Twig\Node\Node
    {
        if (self::OPTIMIZE_FOR === (self::OPTIMIZE_FOR & $this->optimizers)) {
            $this->leave_optimize_for($node);
        }
        $node = $this->optimize_print_node($node);
        return $node;
    }
    /**
     * Optimizes print nodes.
     *
     * It replaces:
     *
     *   * "echo $this->render(Parent)Block()" with "$this->display(Parent)Block()"
     */
    private function optimize_print_node(Node $node): Node
    {
        if (!$node instanceof Print_Node) {
            return $node;
        }
        $expr_node = $node->get_node('expr');
        if ($expr_node instanceof Constant_Expression && \is_string($expr_node->get_attribute('value'))) {
            return new Text_Node($expr_node->get_attribute('value'), $expr_node->get_template_line());
        }
        if ($expr_node instanceof Block_Reference_Expression || $expr_node instanceof Parent_Expression) {
            $expr_node->set_attribute('output', true);
            return $expr_node;
        }
        return $node;
    }
    /**
     * Optimizes "for" tag by removing the "loop" variable creation whenever possible.
     */
    private function enter_optimize_for(Node $node): void
    {
        if ($node instanceof For_Node) {
            // disable the loop variable by default
            $node->set_attribute('with_loop', false);
            array_unshift($this->loops, $node);
            array_unshift($this->loops_targets, $node->get_node('value_target')->get_attribute('name'));
            array_unshift($this->loops_targets, $node->get_node('key_target')->get_attribute('name'));
        } elseif (!$this->loops) {
            // we are outside a loop
            return;
        } elseif ($node instanceof Context_Variable && 'loop' === $node->get_attribute('name')) {
            $node->set_attribute('always_defined', true);
            $this->add_loop_to_current();
        } elseif ($node instanceof Context_Variable && \in_array($node->get_attribute('name'), $this->loops_targets, true)) {
            $node->set_attribute('always_defined', true);
        } elseif ($node instanceof Block_Reference_Node || $node instanceof Block_Reference_Expression) {
            $this->add_loop_to_current();
        } elseif ($node instanceof Include_Node && !$node->get_attribute('only')) {
            $this->add_loop_to_all();
        } elseif ($node instanceof Function_Expression && 'include' === $node->get_attribute('name') && (!$node->get_node('arguments')->has_node('with_context') || false !== $node->get_node('arguments')->get_node('with_context')->get_attribute('value'))) {
            $this->add_loop_to_all();
        } elseif ($node instanceof Get_Attr_Expression && (!$node->get_node('attribute') instanceof Constant_Expression || 'parent' === $node->get_node('attribute')->get_attribute('value')) && (true === $this->loops[0]->get_attribute('with_loop') || $node->get_node('node') instanceof Context_Variable && 'loop' === $node->get_node('node')->get_attribute('name'))) {
            $this->add_loop_to_all();
        }
    }
    /**
     * Optimizes "for" tag by removing the "loop" variable creation whenever possible.
     */
    private function leave_optimize_for(Node $node): void
    {
        if ($node instanceof For_Node) {
            array_shift($this->loops);
            array_shift($this->loops_targets);
            array_shift($this->loops_targets);
        }
    }
    private function add_loop_to_current(): void
    {
        $this->loops[0]->set_attribute('with_loop', true);
    }
    private function add_loop_to_all(): void
    {
        foreach ($this->loops as $loop) {
            $loop->set_attribute('with_loop', true);
        }
    }
    public function get_priority(): int
    {
        return 255;
    }
}