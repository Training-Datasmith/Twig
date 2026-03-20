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
use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Variable\Assign_Context_Variable;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\Node;
/**
 * Represents an arrow function.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Arrow_Function_Expression extends Abstract_Expression
{
    public function __construct(Abstract_Expression $expr, Node $names, int $lineno)
    {
        if ($names instanceof Context_Variable) {
            $names = new List_Expression([new Assign_Context_Variable($names->get_attribute('name'), $names->get_template_line())], $lineno);
        }
        if (!$names instanceof List_Expression) {
            throw new Syntax_Error('The arrow function argument must be a list of variables or a single variable.', $names->get_template_line(), $names->get_source_context());
        }
        parent::__construct(['expr' => $expr, 'names' => $names], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this)->raw('function (')->subcompile($this->get_node('names'))->raw(') use ($context, $macros) { ');
        foreach ($this->get_node('names') as $name) {
            $compiler->raw('$context["')->raw($name->get_attribute('name'))->raw('"] = $__')->raw($name->get_attribute('name'))->raw('__; ');
        }
        $compiler->raw('return ')->subcompile($this->get_node('expr'))->raw('; }');
    }
}