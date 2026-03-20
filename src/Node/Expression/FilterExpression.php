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

use Twig\Attribute\First_Class_Twig_Callable_Ready;
use Twig\Compiler;
use Twig\Node\Name_Deprecation;
use Twig\Node\Node;
use Twig\Twig_Filter;
class Filter_Expression extends Call_Expression
{
    /**
     * @param AbstractExpression $node
     */
    #[First_Class_Twig_Callable_Ready]
    public function __construct(Node $node, Twig_Filter|Constant_Expression $filter, Node $arguments, int $lineno)
    {
        if (!$node instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance to the "node" argument of "%s" is deprecated ("%s" given).', Abstract_Expression::class, static::class, $node::class);
        }
        if ($filter instanceof Twig_Filter) {
            $name = $filter->get_name();
            $filter_name = new Constant_Expression($name, $lineno);
        } else {
            $name = $filter->get_attribute('value');
            $filter_name = $filter;
            trigger_deprecation('twig/twig', '3.12', 'Not passing an instance of "TwigFilter" when creating a "%s" filter of type "%s" is deprecated.', $name, static::class);
        }
        parent::__construct(['node' => $node, 'filter' => $filter_name, 'arguments' => $arguments], ['name' => $name, 'type' => 'filter'], $lineno);
        if ($filter instanceof Twig_Filter) {
            $this->set_attribute('twig_callable', $filter);
        }
        $this->deprecate_node('filter', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('needs_charset', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('needs_environment', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('needs_context', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('arguments', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('callable', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('is_variadic', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('dynamic_name', new Name_Deprecation('twig/twig', '3.12'));
    }
    public function compile(Compiler $compiler): void
    {
        $name = $this->get_node('filter', false)->get_attribute('value');
        if ($name !== $this->get_attribute('name')) {
            trigger_deprecation('twig/twig', '3.11', 'Changing the value of a "filter" node in a NodeVisitor class is not supported anymore.');
            $this->remove_attribute('twig_callable');
        }
        if ('raw' === $name) {
            trigger_deprecation('twig/twig', '3.11', 'Creating the "raw" filter via "FilterExpression" is deprecated; use "RawFilter" instead.');
            $compiler->subcompile($this->get_node('node'));
            return;
        }
        if (!$this->has_attribute('twig_callable')) {
            $this->set_attribute('twig_callable', $compiler->get_environment()->get_filter($name));
        }
        $this->compile_callable($compiler);
    }
}