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
use Twig\Node\Expression\Constant_Expression;
/**
 * Represents a set node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Set_Node extends Node implements Node_Capture_Interface
{
    public function __construct(bool $capture, Node $names, Node $values, int $lineno)
    {
        /*
         * Optimizes the node when capture is used for a large block of text.
         *
         * {% set foo %}foo{% endset %} is compiled to $context['foo'] = new Twig\Markup("foo");
         */
        $safe = false;
        if ($capture) {
            $safe = true;
            // Node::class === get_class($values) should be removed in Twig 4.0
            if (($values instanceof Nodes || Node::class === $values::class) && !\count($values)) {
                $values = new Constant_Expression('', $values->get_template_line());
                $capture = false;
            } elseif ($values instanceof Text_Node) {
                $values = new Constant_Expression($values->get_attribute('data'), $values->get_template_line());
                $capture = false;
            } elseif ($values instanceof Print_Node && $values->get_node('expr') instanceof Constant_Expression) {
                $values = $values->get_node('expr');
                $capture = false;
            } else {
                $values = new Capture_Node($values, $values->get_template_line());
            }
        }
        parent::__construct(['names' => $names, 'values' => $values], ['capture' => $capture, 'safe' => $safe], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this);
        if (\count($this->get_node('names')) > 1) {
            $compiler->write('[');
            foreach ($this->get_node('names') as $idx => $node) {
                if ($idx) {
                    $compiler->raw(', ');
                }
                $compiler->subcompile($node);
            }
            $compiler->raw(']');
        } else {
            $compiler->subcompile($this->get_node('names'), false);
        }
        $compiler->raw(' = ');
        if ($this->get_attribute('capture')) {
            $compiler->subcompile($this->get_node('values'));
        } else {
            if (\count($this->get_node('names')) > 1) {
                $compiler->write('[');
                foreach ($this->get_node('values') as $idx => $value) {
                    if ($idx) {
                        $compiler->raw(', ');
                    }
                    $compiler->subcompile($value);
                }
                $compiler->raw(']');
            } else if ($this->get_attribute('safe')) {
                if ($this->get_node('values') instanceof Constant_Expression) {
                    if ('' === $this->get_node('values')->get_attribute('value')) {
                        $compiler->raw('""');
                    } else {
                        $compiler->raw('new Markup(')->subcompile($this->get_node('values'))->raw(', $this->env->getCharset())');
                    }
                } else {
                    $compiler->raw("('' === \$tmp = ")->subcompile($this->get_node('values'))->raw(") ? '' : new Markup(\$tmp, \$this->env->getCharset())");
                }
            } else {
                $compiler->subcompile($this->get_node('values'));
            }
            $compiler->raw(';');
        }
        $compiler->raw("\n");
    }
}