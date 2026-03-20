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
namespace Twig\Node\Expression;

use Twig\Compiler;
use Twig\Node\Expression\Variable\Context_Variable;
class Method_Call_Expression extends Abstract_Expression implements Support_Defined_Test_Interface
{
    use Support_Defined_Test_Deprecation_Trait;
    use Support_Defined_Test_Trait;
    public function __construct(Abstract_Expression $node, string $method, Array_Expression $arguments, int $lineno)
    {
        trigger_deprecation('twig/twig', '3.15', 'The "%s" class is deprecated, use "%s" instead.', self::class, Macro_Reference_Expression::class);
        parent::__construct(['node' => $node, 'arguments' => $arguments], ['method' => $method, 'safe' => false], $lineno);
        if ($node instanceof Context_Variable) {
            $node->set_attribute('always_defined', true);
        }
    }
    public function compile(Compiler $compiler): void
    {
        if ($this->defined_test) {
            $compiler->raw('method_exists($macros[')->repr($this->get_node('node')->get_attribute('name'))->raw('], ')->repr($this->get_attribute('method'))->raw(')');
            return;
        }
        $compiler->raw('CoreExtension::callMacro($macros[')->repr($this->get_node('node')->get_attribute('name'))->raw('], ')->repr($this->get_attribute('method'))->raw(', ')->subcompile($this->get_node('arguments'))->raw(', ')->repr($this->get_template_line())->raw(', $context, $this->getSourceContext())');
    }
}