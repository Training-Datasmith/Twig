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
namespace Twig\Node\Expression;

use Twig\Compiler;
use Twig\Node\Node;
/**
 * Represents a block call node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Block_Reference_Expression extends Abstract_Expression implements Support_Defined_Test_Interface
{
    use Support_Defined_Test_Deprecation_Trait;
    use Support_Defined_Test_Trait;
    /**
     * @param AbstractExpression $name
     */
    public function __construct(Node $name, ?Node $template, int $lineno)
    {
        if (!$name instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance to the "node" argument of "%s" is deprecated ("%s" given).', Abstract_Expression::class, static::class, $name::class);
        }
        $nodes = ['name' => $name];
        if (null !== $template) {
            $nodes['template'] = $template;
        }
        parent::__construct($nodes, ['output' => false], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        if ($this->defined_test) {
            $this->compile_template_call($compiler, 'hasBlock');
        } else if ($this->get_attribute('output')) {
            $compiler->add_debug_info($this);
            $compiler->write('yield from ');
            $this->compile_template_call($compiler, 'yieldBlock')->raw(";\n");
        } else {
            $this->compile_template_call($compiler, 'renderBlock');
        }
    }
    private function compile_template_call(Compiler $compiler, string $method): Compiler
    {
        if (!$this->has_node('template')) {
            $compiler->write('$this');
        } else {
            $compiler->write('$this->load(')->subcompile($this->get_node('template'))->raw(', ')->repr($this->get_template_line())->raw(')');
        }
        $compiler->raw(\sprintf('->unwrap()->%s', $method));
        return $this->compile_block_arguments($compiler);
    }
    private function compile_block_arguments(Compiler $compiler): Compiler
    {
        $compiler->raw('(')->subcompile($this->get_node('name'))->raw(', $context');
        if (!$this->has_node('template')) {
            $compiler->raw(', $blocks');
        }
        return $compiler->raw(')');
    }
}