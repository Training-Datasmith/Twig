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
namespace Twig\Node\Expression\Binary;

use Twig\Compiler;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Node;
abstract class Abstract_Binary extends Abstract_Expression implements Binary_Interface
{
    /**
     * @param AbstractExpression $left
     * @param AbstractExpression $right
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        if (!$left instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance to the "left" argument of "%s" is deprecated ("%s" given).', Abstract_Expression::class, static::class, $left::class);
        }
        if (!$right instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance to the "right" argument of "%s" is deprecated ("%s" given).', Abstract_Expression::class, static::class, $right::class);
        }
        parent::__construct(['left' => $left, 'right' => $right], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('(')->subcompile($this->get_node('left'))->raw(' ');
        $this->operator($compiler);
        $compiler->raw(' ')->subcompile($this->get_node('right'))->raw(')');
    }
    abstract public function operator(Compiler $compiler): Compiler;
}