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
namespace Twig\Node\Expression\Unary;

use Twig\Compiler;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Node;
abstract class Abstract_Unary extends Abstract_Expression implements Unary_Interface
{
    /**
     * @param AbstractExpression $node
     */
    public function __construct(Node $node, int $lineno)
    {
        if (!$node instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance argument to "%s" is deprecated ("%s" given).', Abstract_Expression::class, static::class, $node::class);
        }
        parent::__construct(['node' => $node], ['with_parentheses' => false], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        if ($this->has_explicit_parentheses()) {
            $compiler->raw('(');
        } else {
            $compiler->raw(' ');
        }
        $this->operator($compiler);
        $compiler->subcompile($this->get_node('node'));
        if ($this->has_explicit_parentheses()) {
            $compiler->raw(')');
        }
    }
    abstract public function operator(Compiler $compiler): Compiler;
}