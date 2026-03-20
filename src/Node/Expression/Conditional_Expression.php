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
use Twig\Node\Expression\Ternary\Conditional_Ternary;
class Conditional_Expression extends Abstract_Expression implements Operator_Escape_Interface
{
    public function __construct(Abstract_Expression $expr1, Abstract_Expression $expr2, Abstract_Expression $expr3, int $lineno)
    {
        trigger_deprecation('twig/twig', '3.17', \sprintf('"%s" is deprecated; use "%s" instead.', self::class, Conditional_Ternary::class));
        parent::__construct(['expr1' => $expr1, 'expr2' => $expr2, 'expr3' => $expr3], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        // Ternary with no then uses Elvis operator
        if ($this->get_node('expr1') === $this->get_node('expr2')) {
            $compiler->raw('((')->subcompile($this->get_node('expr1'))->raw(') ?: (')->subcompile($this->get_node('expr3'))->raw('))');
        } else {
            $compiler->raw('((')->subcompile($this->get_node('expr1'))->raw(') ? (')->subcompile($this->get_node('expr2'))->raw(') : (')->subcompile($this->get_node('expr3'))->raw('))');
        }
    }
    public function get_operand_names_to_escape(): array
    {
        return ['expr2', 'expr3'];
    }
}