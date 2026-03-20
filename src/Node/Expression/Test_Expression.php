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

use Twig\Attribute\First_Class_Twig_Callable_Ready;
use Twig\Compiler;
use Twig\Node\Name_Deprecation;
use Twig\Node\Node;
use Twig\Twig_Test;
class Test_Expression extends Call_Expression implements Return_Bool_Interface
{
    #[First_Class_Twig_Callable_Ready]
    public function __construct(Node $node, string|Twig_Test $test, ?Node $arguments, int $lineno)
    {
        if (!$node instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance to the "node" argument of "%s" is deprecated ("%s" given).', Abstract_Expression::class, static::class, $node::class);
        }
        $nodes = ['node' => $node];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }
        if ($test instanceof Twig_Test) {
            $name = $test->get_name();
        } else {
            $name = $test;
            trigger_deprecation('twig/twig', '3.12', 'Not passing an instance of "TwigTest" when creating a "%s" test of type "%s" is deprecated.', $name, static::class);
        }
        parent::__construct($nodes, ['name' => $name, 'type' => 'test'], $lineno);
        if ($test instanceof Twig_Test) {
            $this->set_attribute('twig_callable', $test);
        }
        $this->deprecate_attribute('arguments', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('callable', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('is_variadic', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('dynamic_name', new Name_Deprecation('twig/twig', '3.12'));
    }
    public function compile(Compiler $compiler): void
    {
        $name = $this->get_attribute('name');
        if ($this->has_attribute('twig_callable')) {
            $name = $this->get_attribute('twig_callable')->get_name();
            if ($name !== $this->get_attribute('name')) {
                trigger_deprecation('twig/twig', '3.12', 'Changing the value of a "test" node in a NodeVisitor class is not supported anymore.');
                $this->remove_attribute('twig_callable');
            }
        }
        if (!$this->has_attribute('twig_callable')) {
            $this->set_attribute('twig_callable', $compiler->get_environment()->get_test($this->get_attribute('name')));
        }
        $this->compile_callable($compiler);
    }
}