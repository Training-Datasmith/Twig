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
namespace Twig\Node\Expression\Binary;

use Twig\Compiler;
use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Empty_Expression;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\Node;
/**
 * @internal
 */
class Sequence_Destructuring_Set_Binary extends Abstract_Binary
{
    private array $variables = [];
    /**
     * @param ArrayExpression    $left  The array expression containing variables to assign to
     * @param AbstractExpression $right The expression providing values for assignment
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        foreach ($left->get_key_value_pairs() as $pair) {
            if ($pair['value'] instanceof Empty_Expression) {
                $this->variables[] = null;
            } elseif ($pair['value'] instanceof Context_Variable) {
                $this->variables[] = $pair['value']->get_attribute('name');
            } else {
                throw new Syntax_Error(\sprintf('Cannot assign to "%s", only variables can be assigned in sequence destructuring.', $pair['value']::class), $lineno);
            }
        }
        parent::__construct($left, $right, $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this);
        $compiler->raw('[');
        foreach ($this->variables as $i => $name) {
            if ($i) {
                $compiler->raw(', ');
            }
            if (null !== $name) {
                $compiler->raw('$context[')->repr($name)->raw(']');
            }
        }
        $compiler->raw('] = array_pad(')->subcompile($this->get_node('right'))->raw(', ')->repr(\count($this->variables))->raw(', null)');
    }
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('=');
    }
}