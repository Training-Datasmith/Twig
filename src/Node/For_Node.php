<?php

declare (strict_types=1);
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Twig\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Variable\Assign_Context_Variable;
/**
 * Represents a for node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class For_Node extends Node
{
    private ?\Twig\Node\For_Loop_Node $loop = null;
    public function __construct(Assign_Context_Variable $key_target, Assign_Context_Variable $value_target, Abstract_Expression $seq, ?Node $ifexpr, Node $body, ?Node $else, int $lineno)
    {
        $body = new Nodes([$body, $this->loop = new For_Loop_Node($lineno)]);
        if (null !== $ifexpr) {
            trigger_deprecation('twig/twig', '3.19', \sprintf('Passing not-null to the "ifexpr" argument of the "%s" constructor is deprecated.', static::class));
        }
        if (null !== $else && !$else instanceof For_Else_Node) {
            trigger_deprecation('twig/twig', '3.19', \sprintf('Not passing an instance of "%s" to the "else" argument of the "%s" constructor is deprecated.', For_Else_Node::class, static::class));
            $else = new For_Else_Node($else, $else->get_template_line());
        }
        $nodes = ['key_target' => $key_target, 'value_target' => $value_target, 'seq' => $seq, 'body' => $body];
        if (null !== $else) {
            $nodes['else'] = $else;
        }
        parent::__construct($nodes, ['with_loop' => true], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this)->write("\$context['_parent'] = \$context;\n")->write("\$context['_seq'] = CoreExtension::ensureTraversable(")->subcompile($this->get_node('seq'))->raw(");\n");
        if ($this->has_node('else')) {
            $compiler->write("\$context['_iterated'] = false;\n");
        }
        if ($this->get_attribute('with_loop')) {
            $compiler->write("\$context['loop'] = [\n")->write("  'parent' => \$context['_parent'],\n")->write("  'index0' => 0,\n")->write("  'index'  => 1,\n")->write("  'first'  => true,\n")->write("];\n")->write("if (is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof \\Countable)) {\n")->indent()->write("\$length = count(\$context['_seq']);\n")->write("\$context['loop']['revindex0'] = \$length - 1;\n")->write("\$context['loop']['revindex'] = \$length;\n")->write("\$context['loop']['length'] = \$length;\n")->write("\$context['loop']['last'] = 1 === \$length;\n")->outdent()->write("}\n");
        }
        $this->loop->set_attribute('else', $this->has_node('else'));
        $this->loop->set_attribute('with_loop', $this->get_attribute('with_loop'));
        $compiler->write("foreach (\$context['_seq'] as ")->subcompile($this->get_node('key_target'))->raw(' => ')->subcompile($this->get_node('value_target'))->raw(") {\n")->indent()->subcompile($this->get_node('body'))->outdent()->write("}\n");
        if ($this->has_node('else')) {
            $compiler->subcompile($this->get_node('else'));
        }
        $compiler->write("\$_parent = \$context['_parent'];\n");
        // remove some "private" loop variables (needed for nested loops)
        $compiler->write('unset($context[\'_seq\'], $context[\'' . $this->get_node('key_target')->get_attribute('name') . '\'], $context[\'' . $this->get_node('value_target')->get_attribute('name') . '\'], $context[\'_parent\']');
        if ($this->has_node('else')) {
            $compiler->raw(', $context[\'_iterated\']');
        }
        if ($this->get_attribute('with_loop')) {
            $compiler->raw(', $context[\'loop\']');
        }
        $compiler->raw(");\n");
        // keep the values set in the inner context for variables defined in the outer context
        $compiler->write("\$context = array_intersect_key(\$context, \$_parent) + \$_parent;\n");
    }
}