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
namespace Twig\Profiler\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
use Twig\Node\Node;
/**
 * Represents a profile enter node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Enter_Profile_Node extends Node
{
    public function __construct(string $extension_name, string $type, string $name, string $var_name)
    {
        parent::__construct([], ['extension_name' => $extension_name, 'name' => $name, 'type' => $type, 'var_name' => $var_name]);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->write(\sprintf('$%s = $this->extensions[', $this->get_attribute('var_name')))->repr($this->get_attribute('extension_name'))->raw("];\n")->write(\sprintf('$%s->enter($%s = new \Twig\Profiler\Profile($this->getTemplateName(), ', $this->get_attribute('var_name'), $this->get_attribute('var_name') . '_prof'))->repr($this->get_attribute('type'))->raw(', ')->repr($this->get_attribute('name'))->raw("));\n\n");
    }
}