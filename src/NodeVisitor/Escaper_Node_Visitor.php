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
use Twig\Extension\Escaper_Extension;
use Twig\Node\Auto_Escape_Node;
use Twig\Node\Block_Node;
use Twig\Node\Block_Reference_Node;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Filter_Expression;
use Twig\Node\Expression\Operator_Escape_Interface;
use Twig\Node\Import_Node;
use Twig\Node\Module_Node;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\Print_Node;
use Twig\Node_Traverser;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class Escaper_Node_Visitor implements Node_Visitor_Interface
{
    private array $status_stack = [];
    private array $blocks = [];
    private readonly \Twig\Node_Visitor\Safe_Analysis_Node_Visitor $safe_analysis;
    private ?\Twig\Node_Traverser $traverser = null;
    private $default_strategy = false;
    private array $safe_vars = [];
    public function __construct()
    {
        $this->safe_analysis = new Safe_Analysis_Node_Visitor();
    }
    public function enter_node(Node $node, Environment $env): Node
    {
        if ($node instanceof Module_Node) {
            if ($env->has_extension(Escaper_Extension::class) && $default_strategy = $env->get_extension(Escaper_Extension::class)->get_default_strategy($node->get_template_name())) {
                $this->default_strategy = $default_strategy;
            }
            $this->safe_vars = [];
            $this->blocks = [];
        } elseif ($node instanceof Auto_Escape_Node) {
            $this->status_stack[] = $node->get_attribute('value');
        } elseif ($node instanceof Block_Node) {
            $this->status_stack[] = $this->blocks[$node->get_attribute('name')] ?? $this->need_escaping();
        } elseif ($node instanceof Import_Node) {
            $this->safe_vars[] = $node->get_node('var')->get_node('var')->get_attribute('name');
        }
        return $node;
    }
    public function leave_node(Node $node, Environment $env): \Twig\Node\Node
    {
        if ($node instanceof Module_Node) {
            $this->default_strategy = false;
            $this->safe_vars = [];
            $this->blocks = [];
        } elseif ($node instanceof Filter_Expression) {
            return $this->pre_escape_filter_node($node, $env);
        } elseif ($node instanceof Print_Node && false !== $type = $this->need_escaping()) {
            if (true === $type) {
                $type = 'html';
            }
            $expression = $node->get_node('expr');
            if ($expression instanceof Operator_Escape_Interface) {
                $this->escape_conditional($expression, $env, $type);
            } else {
                $node->set_node('expr', $this->escape_expression($expression, $env, $type));
            }
            return $node;
        }
        if ($node instanceof Auto_Escape_Node || $node instanceof Block_Node) {
            array_pop($this->status_stack);
        } elseif ($node instanceof Block_Reference_Node) {
            $this->blocks[$node->get_attribute('name')] = $this->need_escaping();
        }
        return $node;
    }
    /**
     * @param AbstractExpression&OperatorEscapeInterface $expression
     */
    private function escape_conditional($expression, Environment $env, string $type): void
    {
        foreach ($expression->get_operand_names_to_escape() as $name) {
            /** @var AbstractExpression $operand */
            $operand = $expression->get_node($name);
            if ($operand instanceof Operator_Escape_Interface) {
                $this->escape_conditional($operand, $env, $type);
            } else {
                $expression->set_node($name, $this->escape_expression($operand, $env, $type));
            }
        }
    }
    private function escape_expression(Abstract_Expression $expression, Environment $env, string $type): Abstract_Expression
    {
        return $this->is_safe_for($type, $expression, $env) ? $expression : $this->get_escaper_filter($env, $type, $expression);
    }
    private function pre_escape_filter_node(Filter_Expression $filter, Environment $env): Filter_Expression
    {
        if ($filter->has_attribute('twig_callable')) {
            $type = $filter->get_attribute('twig_callable')->get_pre_escape();
        } else {
            // legacy
            $name = $filter->get_node('filter', false)->get_attribute('value');
            $type = $env->get_filter($name)->get_pre_escape();
        }
        if (null === $type) {
            return $filter;
        }
        /** @var AbstractExpression $node */
        $node = $filter->get_node('node');
        if ($this->is_safe_for($type, $node, $env)) {
            return $filter;
        }
        $filter->set_node('node', $this->get_escaper_filter($env, $type, $node));
        return $filter;
    }
    private function is_safe_for(string $type, Abstract_Expression $expression, Environment $env): bool
    {
        $safe = $this->safe_analysis->get_safe($expression);
        if (!$safe) {
            if (null === $this->traverser) {
                $this->traverser = new Node_Traverser($env, [$this->safe_analysis]);
            }
            $this->safe_analysis->set_safe_vars($this->safe_vars);
            $this->traverser->traverse($expression);
            $safe = $this->safe_analysis->get_safe($expression);
        }
        return \in_array($type, $safe, true) || \in_array('all', $safe, true);
    }
    /**
     * @return string|false
     */
    private function need_escaping(): string|bool
    {
        if (\count($this->status_stack)) {
            return $this->status_stack[\count($this->status_stack) - 1];
        }
        return $this->default_strategy ?: false;
    }
    private function get_escaper_filter(Environment $env, string $type, Abstract_Expression $node): Filter_Expression
    {
        $line = $node->get_template_line();
        $filter = $env->get_filter('escape');
        $args = new Nodes([new Constant_Expression($type, $line), new Constant_Expression(null, $line), new Constant_Expression(true, $line)]);
        return new Filter_Expression($node, $filter, $args, $line);
    }
    public function get_priority(): int
    {
        return 0;
    }
}