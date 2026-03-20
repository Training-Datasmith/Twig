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
use Twig\Node\Expression\Variable\Assign_Template_Variable;
use Twig\Node\Expression\Variable\Context_Variable;
/**
 * Represents an import node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Import_Node extends Node
{
    public function __construct(Abstract_Expression $expr, Abstract_Expression|Assign_Template_Variable $var, int $lineno)
    {
        if (\func_num_args() > 3) {
            trigger_deprecation('twig/twig', '3.15', \sprintf('Passing more than 3 arguments to "%s()" is deprecated.', __METHOD__));
        }
        if (!$var instanceof Assign_Template_Variable) {
            trigger_deprecation('twig/twig', '3.15', \sprintf('Passing a "%s" instance as the second argument of "%s" is deprecated, pass a "%s" instead.', $var::class, self::class, Assign_Template_Variable::class));
            $var = new Assign_Template_Variable($var->get_attribute('name'), $lineno);
        }
        parent::__construct(['expr' => $expr, 'var' => $var], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->subcompile($this->get_node('var'));
        if ($this->get_node('expr') instanceof Context_Variable && '_self' === $this->get_node('expr')->get_attribute('name')) {
            $compiler->raw('$this');
        } else {
            $compiler->raw('$this->load(')->subcompile($this->get_node('expr'))->raw(', ')->repr($this->get_template_line())->raw(')->unwrap()');
        }
        $compiler->raw(";\n");
    }
}