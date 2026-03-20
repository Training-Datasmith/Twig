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
namespace Twig\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
/**
 * Represents a nested "with" scope.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class With_Node extends Node
{
    public function __construct(Node $body, ?Node $variables, bool $only, int $lineno)
    {
        $nodes = ['body' => $body];
        if (null !== $variables) {
            $nodes['variables'] = $variables;
        }
        parent::__construct($nodes, ['only' => $only], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this);
        $parent_context_name = $compiler->get_var_name();
        $compiler->write(\sprintf("\$%s = \$context;\n", $parent_context_name));
        if ($this->has_node('variables')) {
            $node = $this->get_node('variables');
            $vars_name = $compiler->get_var_name();
            $compiler->write(\sprintf('$%s = ', $vars_name))->subcompile($node)->raw(";\n")->write(\sprintf("if (!is_iterable(\$%s)) {\n", $vars_name))->indent()->write("throw new RuntimeError('Variables passed to the \"with\" tag must be a mapping.', ")->repr($node->get_template_line())->raw(", \$this->getSourceContext());\n")->outdent()->write("}\n")->write(\sprintf("\$%s = CoreExtension::toArray(\$%s);\n", $vars_name, $vars_name));
            if ($this->get_attribute('only')) {
                $compiler->write("\$context = [];\n");
            }
            $compiler->write(\sprintf("\$context = \$%s + \$context + \$this->env->getGlobals();\n", $vars_name));
        }
        $compiler->subcompile($this->get_node('body'))->write(\sprintf("\$context = \$%s;\n", $parent_context_name));
    }
}