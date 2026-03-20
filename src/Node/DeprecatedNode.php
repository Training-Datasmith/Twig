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
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Constant_Expression;
/**
 * Represents a deprecated node.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
#[Yield_Ready]
class Deprecated_Node extends Node
{
    public function __construct(Abstract_Expression $expr, int $lineno)
    {
        parent::__construct(['expr' => $expr], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this);
        $expr = $this->get_node('expr');
        if (!$expr instanceof Constant_Expression) {
            $var_name = $compiler->get_var_name();
            $compiler->write(\sprintf('$%s = ', $var_name))->subcompile($expr)->raw(";\n");
        }
        $compiler->write('trigger_deprecation(');
        if ($this->has_node('package')) {
            $compiler->subcompile($this->get_node('package'));
        } else {
            $compiler->raw("''");
        }
        $compiler->raw(', ');
        if ($this->has_node('version')) {
            $compiler->subcompile($this->get_node('version'));
        } else {
            $compiler->raw("''");
        }
        $compiler->raw(', ');
        if ($expr instanceof Constant_Expression) {
            $compiler->subcompile($expr);
        } else {
            $compiler->write(\sprintf('$%s', $var_name));
        }
        $compiler->raw('.')->string(\sprintf(' in "%s" at line %d.', $this->get_template_name(), $this->get_template_line()))->raw(");\n");
    }
}