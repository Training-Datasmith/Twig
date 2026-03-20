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
/**
 * Checks if casting an expression to __toString() is allowed by the sandbox.
 *
 * For instance, when there is a simple Print statement, like {{ article }},
 * and if the sandbox is enabled, we need to check that the __toString()
 * method is allowed if 'article' is an object. The same goes for {{ article|upper }}
 * or {{ random(article) }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Check_To_String_Node extends Abstract_Expression
{
    public function __construct(Abstract_Expression $expr)
    {
        parent::__construct(['expr' => $expr], [], $expr->get_template_line());
    }
    public function compile(Compiler $compiler): void
    {
        $expr = $this->get_node('expr');
        $compiler->raw('$this->sandbox->ensureToStringAllowed(')->subcompile($expr)->raw(', ')->repr($expr->get_template_line())->raw(', $this->source)');
    }
}