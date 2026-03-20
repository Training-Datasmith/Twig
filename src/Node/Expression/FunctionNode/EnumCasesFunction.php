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
namespace Twig\Node\Expression\Function_Node;

use Twig\Compiler;
use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Function_Expression;
class Enum_Cases_Function extends Function_Expression
{
    public function compile(Compiler $compiler): void
    {
        $arguments = $this->get_node('arguments');
        if ($arguments->has_node('enum')) {
            $first_argument = $arguments->get_node('enum');
        } elseif ($arguments->has_node('0')) {
            $first_argument = $arguments->get_node('0');
        } else {
            $first_argument = null;
        }
        if (!$first_argument instanceof Constant_Expression || 1 !== \count($arguments)) {
            parent::compile($compiler);
            return;
        }
        $value = $first_argument->get_attribute('value');
        if (!\is_string($value)) {
            throw new Syntax_Error('The first argument of the "enum_cases" function must be a string.', $this->get_template_line(), $this->get_source_context());
        }
        if (!enum_exists($value)) {
            throw new Syntax_Error(\sprintf('The first argument of the "enum_cases" function must be the name of an enum, "%s" given.', $value), $this->get_template_line(), $this->get_source_context());
        }
        $compiler->raw(\sprintf('%s::cases()', $value));
    }
}