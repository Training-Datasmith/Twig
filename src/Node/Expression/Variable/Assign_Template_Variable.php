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
namespace Twig\Node\Expression\Variable;

use Twig\Compiler;
use Twig\Node\Expression\Abstract_Expression;
final class Assign_Template_Variable extends Abstract_Expression
{
    public function __construct(Template_Variable $var, bool $global = true)
    {
        parent::__construct(['var' => $var], ['global' => $global], $var->get_template_line());
    }
    public function compile(Compiler $compiler): void
    {
        /** @var TemplateVariable $var */
        $var = $this->nodes['var'];
        $compiler->add_debug_info($this)->write('$macros[')->string($var->get_name($compiler))->raw('] = ');
        if ($this->get_attribute('global')) {
            $compiler->raw('$this->macros[')->string($var->get_name($compiler))->raw('] = ');
        }
    }
}