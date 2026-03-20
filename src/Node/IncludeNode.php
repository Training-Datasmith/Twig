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
/**
 * Represents an include node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Include_Node extends Node implements Node_Output_Interface
{
    public function __construct(Abstract_Expression $expr, ?Abstract_Expression $variables, bool $only, bool $ignore_missing, int $lineno)
    {
        $nodes = ['expr' => $expr];
        if (null !== $variables) {
            $nodes['variables'] = $variables;
        }
        parent::__construct($nodes, ['only' => $only, 'ignore_missing' => $ignore_missing], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this);
        if ($this->get_attribute('ignore_missing')) {
            $template = $compiler->get_var_name();
            $compiler->write("try {\n")->indent()->write(\sprintf('$%s = ', $template));
            $this->add_get_template($compiler, $template);
            $compiler->raw(";\n")->outdent()->write("} catch (LoaderError \$e) {\n")->indent()->write("// ignore missing template\n")->write(\sprintf("\${$template} = null;\n", $template))->outdent()->write("}\n")->write(\sprintf("if (\$%s) {\n", $template))->indent()->write(\sprintf('yield from $%s->unwrap()->yield(', $template));
            $this->add_template_arguments($compiler);
            $compiler->raw(");\n")->outdent()->write("}\n");
        } else {
            $compiler->write('yield from ');
            $this->add_get_template($compiler);
            $compiler->raw('->unwrap()->yield(');
            $this->add_template_arguments($compiler);
            $compiler->raw(");\n");
        }
    }
    /**
     * @return void
     */
    protected function add_get_template(Compiler $compiler, string $template = ''): void
    {
        $compiler->raw('$this->load(')->subcompile($this->get_node('expr'))->raw(', ')->repr($this->get_template_line())->raw(')');
    }
    /**
     * @return void
     */
    protected function add_template_arguments(Compiler $compiler)
    {
        if (!$this->has_node('variables')) {
            $compiler->raw(false === $this->get_attribute('only') ? '$context' : '[]');
        } elseif (false === $this->get_attribute('only')) {
            $compiler->raw('CoreExtension::merge($context, ')->subcompile($this->get_node('variables'))->raw(')');
        } else {
            $compiler->raw('CoreExtension::toArray(');
            $compiler->subcompile($this->get_node('variables'));
            $compiler->raw(')');
        }
    }
}