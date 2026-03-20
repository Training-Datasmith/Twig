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
use Twig\Node\Expression\Block_Reference_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Filter_Expression;
use Twig\Node\Expression\Function_Expression;
use Twig\Node\Expression\Get_Attr_Expression;
use Twig\Node\Expression\Macro_Reference_Expression;
use Twig\Node\Expression\Method_Call_Expression;
use Twig\Node\Expression\Operator_Escape_Interface;
use Twig\Node\Expression\Parent_Expression;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\Node;
/**
 * @internal
 */
final class Safe_Analysis_Node_Visitor implements Node_Visitor_Interface
{
    private array $data = [];
    private array $safe_vars = [];
    public function set_safe_vars(array $safe_vars): void
    {
        $this->safe_vars = $safe_vars;
    }
    /**
     * @return array
     */
    public function get_safe(Node $node)
    {
        $hash = spl_object_id($node);
        if (!isset($this->data[$hash])) {
            return [];
        }
        foreach ($this->data[$hash] as $bucket) {
            if ($bucket['key'] !== $node) {
                continue;
            }
            if (\in_array('html_attr', $bucket['value'], true)) {
                $bucket['value'][] = 'html';
                $bucket['value'][] = 'html_attr_relaxed';
            }
            if (\in_array('html_attr_relaxed', $bucket['value'], true)) {
                $bucket['value'][] = 'html';
            }
            return $bucket['value'];
        }
        return [];
    }
    private function set_safe(Node $node, array $safe): void
    {
        $hash = spl_object_id($node);
        if (isset($this->data[$hash])) {
            foreach ($this->data[$hash] as &$bucket) {
                if ($bucket['key'] === $node) {
                    $bucket['value'] = $safe;
                    return;
                }
            }
        }
        $this->data[$hash][] = ['key' => $node, 'value' => $safe];
    }
    public function enter_node(Node $node, Environment $env): Node
    {
        return $node;
    }
    public function leave_node(Node $node, Environment $env): \Twig\Node\Node
    {
        if ($node instanceof Constant_Expression) {
            // constants are marked safe for all
            $this->set_safe($node, ['all']);
        } elseif ($node instanceof Block_Reference_Expression) {
            // blocks are safe by definition
            $this->set_safe($node, ['all']);
        } elseif ($node instanceof Parent_Expression) {
            // parent block is safe by definition
            $this->set_safe($node, ['all']);
        } elseif ($node instanceof Operator_Escape_Interface) {
            // intersect safeness of operands
            $operands = $node->get_operand_names_to_escape();
            if (2 < \count($operands)) {
                throw new \LogicException(\sprintf('Operators with more than 2 operands are not supported yet, got %d.', \count($operands)));
            }
            if (2 === \count($operands)) {
                $safe = $this->intersect_safe($this->get_safe($node->get_node($operands[0])), $this->get_safe($node->get_node($operands[1])));
                $this->set_safe($node, $safe);
            }
        } elseif ($node instanceof Filter_Expression) {
            // filter expression is safe when the filter is safe
            if ($node->has_attribute('twig_callable')) {
                $filter = $node->get_attribute('twig_callable');
            } else {
                // legacy
                $filter = $env->get_filter($node->get_attribute('name'));
            }
            if ($filter) {
                $safe = $filter->get_safe($node->get_node('arguments'));
                if (null === $safe) {
                    trigger_deprecation('twig/twig', '3.16', 'The "%s::getSafe()" method should not return "null" anymore, return "[]" instead.', $filter::class);
                    $safe = [];
                }
                if (!$safe) {
                    $safe = $this->intersect_safe($this->get_safe($node->get_node('node')), $filter->get_preserves_safety());
                }
                $this->set_safe($node, $safe);
            }
        } elseif ($node instanceof Function_Expression) {
            // function expression is safe when the function is safe
            if ($node->has_attribute('twig_callable')) {
                $function = $node->get_attribute('twig_callable');
            } else {
                // legacy
                $function = $env->get_function($node->get_attribute('name'));
            }
            if ($function) {
                $safe = $function->get_safe($node->get_node('arguments'));
                if (null === $safe) {
                    trigger_deprecation('twig/twig', '3.16', 'The "%s::getSafe()" method should not return "null" anymore, return "[]" instead.', $function::class);
                    $safe = [];
                }
                $this->set_safe($node, $safe);
            }
        } elseif ($node instanceof Method_Call_Expression || $node instanceof Macro_Reference_Expression) {
            // all macro calls are safe
            $this->set_safe($node, ['all']);
        } elseif ($node instanceof Get_Attr_Expression && $node->get_node('node') instanceof Context_Variable) {
            $name = $node->get_node('node')->get_attribute('name');
            if (\in_array($name, $this->safe_vars, true)) {
                $this->set_safe($node, ['all']);
            }
        }
        return $node;
    }
    private function intersect_safe(array $a, array $b): array
    {
        if (!$a || !$b) {
            return [];
        }
        if (\in_array('all', $a, true)) {
            return $b;
        }
        if (\in_array('all', $b, true)) {
            return $a;
        }
        return array_intersect($a, $b);
    }
    public function get_priority(): int
    {
        return 0;
    }
}