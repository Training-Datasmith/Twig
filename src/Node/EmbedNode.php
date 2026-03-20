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
use Twig\Node\Expression\Constant_Expression;
/**
 * Represents an embed node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Embed_Node extends Include_Node
{
    // we don't inject the module to avoid node visitors to traverse it twice (as it will be already visited in the main module)
    public function __construct(string $name, int $index, ?Abstract_Expression $variables, bool $only, bool $ignore_missing, int $lineno)
    {
        parent::__construct(new Constant_Expression('not_used', $lineno), $variables, $only, $ignore_missing, $lineno);
        $this->set_attribute('name', $name);
        $this->set_attribute('index', $index);
    }
    protected function add_get_template(Compiler $compiler, string $template = ''): void
    {
        $compiler->raw('$this->load(')->string($this->get_attribute('name'))->raw(', ')->repr($this->get_template_line())->raw(', ')->repr($this->get_attribute('index'))->raw(')');
        if ($this->get_attribute('ignore_missing')) {
            $compiler->raw(";\n")->write(\sprintf("\$%s->getParent(\$context);\n", $template));
        }
    }
}