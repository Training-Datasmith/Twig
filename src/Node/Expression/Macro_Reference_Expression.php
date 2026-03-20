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
use Twig\Node\Expression\Variable\Template_Variable;
/**
 * Represents a macro call node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Macro_Reference_Expression extends Abstract_Expression implements Support_Defined_Test_Interface
{
    use Support_Defined_Test_Deprecation_Trait;
    use Support_Defined_Test_Trait;
    public function __construct(Template_Variable $template, string $name, Abstract_Expression $arguments, int $lineno)
    {
        parent::__construct(['template' => $template, 'arguments' => $arguments], ['name' => $name], $lineno);
    }
    public function __clone()
    {
        // The template node must not be deep-cloned because its name is
        // lazily generated during compilation and must stay in sync with
        // the AssignTemplateVariable that populates the $macros array.
        $template = $this->nodes['template'];
        parent::__clone();
        $this->nodes['template'] = $template;
    }
    public function compile(Compiler $compiler): void
    {
        if ($this->defined_test) {
            $compiler->subcompile($this->get_node('template'))->raw('->hasMacro(')->repr($this->get_attribute('name'))->raw(', $context')->raw(')');
            return;
        }
        $compiler->subcompile($this->get_node('template'))->raw('->getTemplateForMacro(')->repr($this->get_attribute('name'))->raw(', $context, ')->repr($this->get_template_line())->raw(', $this->getSourceContext())')->raw(\sprintf('->%s', $this->get_attribute('name')))->raw('(...')->subcompile($this->get_node('arguments'))->raw(')');
    }
}