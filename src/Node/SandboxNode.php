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
/**
 * Represents a sandbox node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Sandbox_Node extends Node
{
    public function __construct(Node $body, int $lineno)
    {
        parent::__construct(['body' => $body], [], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this)->write("if (!\$alreadySandboxed = \$this->sandbox->isSandboxed()) {\n")->indent()->write("\$this->sandbox->enableSandbox();\n")->outdent()->write("}\n")->write("try {\n")->indent()->subcompile($this->get_node('body'))->outdent()->write("} finally {\n")->indent()->write("if (!\$alreadySandboxed) {\n")->indent()->write("\$this->sandbox->disableSandbox();\n")->outdent()->write("}\n")->outdent()->write("}\n");
    }
}