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
use Twig\Node\Check_Security_Call_Node;
use Twig\Node\Check_Security_Node;
use Twig\Node\Check_To_String_Node;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Binary\Concat_Binary;
use Twig\Node\Expression\Binary\Range_Binary;
use Twig\Node\Expression\Filter_Expression;
use Twig\Node\Expression\Function_Expression;
use Twig\Node\Expression\Get_Attr_Expression;
use Twig\Node\Expression\Unary\Spread_Unary;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\Module_Node;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\Print_Node;
use Twig\Node\Set_Node;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class Sandbox_Node_Visitor implements Node_Visitor_Interface
{
    private bool $in_a_module = false;
    /** @var array<string, int> */
    private ?array $tags = null;
    /** @var array<string, int> */
    private ?array $filters = null;
    /** @var array<string, int> */
    private ?array $functions = null;
    private bool $needs_to_string_wrap = false;
    public function enter_node(Node $node, Environment $env): Node
    {
        if ($node instanceof Module_Node) {
            $this->in_a_module = true;
            $this->tags = [];
            $this->filters = [];
            $this->functions = [];
            return $node;
        }
        if ($this->in_a_module) {
            // look for tags
            if ($node->get_node_tag() && !isset($this->tags[$node->get_node_tag()])) {
                $this->tags[$node->get_node_tag()] = $node->get_template_line();
            }
            // look for filters
            if ($node instanceof Filter_Expression && !isset($this->filters[$node->get_attribute('name')])) {
                $this->filters[$node->get_attribute('name')] = $node->get_template_line();
            }
            // look for functions
            if ($node instanceof Function_Expression && !isset($this->functions[$node->get_attribute('name')])) {
                $this->functions[$node->get_attribute('name')] = $node->get_template_line();
            }
            // the .. operator is equivalent to the range() function
            if ($node instanceof Range_Binary && !isset($this->functions['range'])) {
                $this->functions['range'] = $node->get_template_line();
            }
            if ($node instanceof Print_Node) {
                $this->needs_to_string_wrap = true;
                $this->wrap_node($node, 'expr');
            }
            if ($node instanceof Set_Node && !$node->get_attribute('capture')) {
                $this->needs_to_string_wrap = true;
            }
            // wrap outer nodes that can implicitly call __toString()
            if ($this->needs_to_string_wrap) {
                if ($node instanceof Concat_Binary) {
                    $this->wrap_node($node, 'left');
                    $this->wrap_node($node, 'right');
                }
                if ($node instanceof Filter_Expression) {
                    $this->wrap_node($node, 'node');
                    $this->wrap_array_node($node, 'arguments');
                }
                if ($node instanceof Function_Expression) {
                    $this->wrap_array_node($node, 'arguments');
                }
            }
        }
        return $node;
    }
    public function leave_node(Node $node, Environment $env): \Twig\Node\Node
    {
        if ($node instanceof Module_Node) {
            $this->in_a_module = false;
            $node->set_node('constructor_end', new Nodes([new Check_Security_Call_Node(), $node->get_node('constructor_end')]));
            $node->set_node('class_end', new Nodes([new Check_Security_Node($this->filters, $this->tags, $this->functions), $node->get_node('class_end')]));
        } elseif ($this->in_a_module) {
            if ($node instanceof Print_Node || $node instanceof Set_Node) {
                $this->needs_to_string_wrap = false;
            }
        }
        return $node;
    }
    private function wrap_node(Node $node, string|int $name): void
    {
        $expr = $node->get_node($name);
        if (($expr instanceof Context_Variable || $expr instanceof Get_Attr_Expression) && !$expr->is_generator()) {
            $node->set_node($name, new Check_To_String_Node($expr));
        } elseif ($expr instanceof Spread_Unary) {
            $this->wrap_node($expr, 'node');
        } elseif ($expr instanceof Array_Expression) {
            foreach ($expr as $name => $_) {
                $this->wrap_node($expr, $name);
            }
        }
    }
    private function wrap_array_node(Node $node, string|int $name): void
    {
        $args = $node->get_node($name);
        foreach ($args as $name => $_) {
            $this->wrap_node($args, $name);
        }
    }
    public function get_priority(): int
    {
        return 0;
    }
}