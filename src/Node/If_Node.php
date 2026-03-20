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
use Twig\Node\Expression\Return_Primitive_Type_Interface;
use Twig\Node\Expression\Test\True_Test;
use Twig\Twig_Test;
/**
 * Represents an if node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class If_Node extends Node
{
    public function __construct(Node $tests, ?Node $else, int $lineno)
    {
        for ($i = 0, $count = \count($tests); $i < $count; $i += 2) {
            $test = $tests->get_node($i);
            if (!$test instanceof Return_Primitive_Type_Interface) {
                $tests->set_node($i, new True_Test($test, new Twig_Test('true'), null, $test->get_template_line()));
            }
        }
        $nodes = ['tests' => $tests];
        if (null !== $else) {
            $nodes['else'] = $else;
        }
        parent::__construct($nodes, [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this);
        for ($i = 0, $count = \count($this->get_node('tests')); $i < $count; $i += 2) {
            if ($i > 0) {
                $compiler->outdent()->write('} elseif (');
            } else {
                $compiler->write('if (');
            }
            $compiler->subcompile($this->get_node('tests')->get_node($i))->raw(") {\n")->indent();
            // The node might not exists if the content is empty
            if ($this->get_node('tests')->has_node($i + 1)) {
                $compiler->subcompile($this->get_node('tests')->get_node($i + 1));
            }
        }
        if ($this->has_node('else')) {
            $compiler->outdent()->write("} else {\n")->indent()->subcompile($this->get_node('else'));
        }
        $compiler->outdent()->write("}\n");
    }
}