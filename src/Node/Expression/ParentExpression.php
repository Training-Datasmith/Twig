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
/**
 * Represents a parent node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Parent_Expression extends Abstract_Expression
{
    public function __construct(string $name, int $lineno)
    {
        parent::__construct([], ['output' => false, 'name' => $name], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        if ($this->get_attribute('output')) {
            $compiler->add_debug_info($this)->write('yield from $this->yieldParentBlock(')->string($this->get_attribute('name'))->raw(", \$context, \$blocks);\n");
        } else {
            $compiler->raw('$this->renderParentBlock(')->string($this->get_attribute('name'))->raw(', $context, $blocks)');
        }
    }
}