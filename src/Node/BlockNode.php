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
namespace Twig\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
/**
 * Represents a block node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Block_Node extends Node
{
    public function __construct(string $name, Node $body, int $lineno)
    {
        parent::__construct(['body' => $body], ['name' => $name], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this)->write("/**\n")->write(" * @return iterable<null|scalar|\\Stringable>\n")->write(" */\n")->write(\sprintf("public function block_%s(array \$context, array \$blocks = []): iterable\n", $this->get_attribute('name')), "{\n")->indent()->write("\$macros = \$this->macros;\n");
        $compiler->subcompile($this->get_node('body'))->write("yield from [];\n")->outdent()->write("}\n\n");
    }
}