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
use Twig\Twig_Function;
class Function_Expression extends Call_Expression implements Support_Defined_Test_Interface
{
    use Support_Defined_Test_Deprecation_Trait;
    use Support_Defined_Test_Trait;
    #[First_Class_Twig_Callable_Ready]
    public function __construct(Twig_Function|string $function, Node $arguments, int $lineno)
    {
        if ($function instanceof Twig_Function) {
            $name = $function->get_name();
        } else {
            $name = $function;
            trigger_deprecation('twig/twig', '3.12', 'Not passing an instance of "TwigFunction" when creating a "%s" function of type "%s" is deprecated.', $name, static::class);
        }
        parent::__construct(['arguments' => $arguments], ['name' => $name, 'type' => 'function'], $lineno);
        if ($function instanceof Twig_Function) {
            $this->set_attribute('twig_callable', $function);
        }
        $this->deprecate_attribute('needs_charset', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('needs_environment', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('needs_context', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('arguments', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('callable', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('is_variadic', new Name_Deprecation('twig/twig', '3.12'));
        $this->deprecate_attribute('dynamic_name', new Name_Deprecation('twig/twig', '3.12'));
    }
    public function enable_defined_test(): void
    {
        if ('constant' === $this->get_attribute('name')) {
            $this->defined_test = true;
        }
    }
    public function compile(Compiler $compiler): void
    {
        $name = $this->get_attribute('name');
        if ($this->has_attribute('twig_callable')) {
            $name = $this->get_attribute('twig_callable')->get_name();
            if ($name !== $this->get_attribute('name')) {
                trigger_deprecation('twig/twig', '3.12', 'Changing the value of a "function" node in a NodeVisitor class is not supported anymore.');
                $this->remove_attribute('twig_callable');
            }
        }
        if (!$this->has_attribute('twig_callable')) {
            $this->set_attribute('twig_callable', $compiler->get_environment()->get_function($name));
        }
        if ('constant' === $name && $this->is_defined_test_enabled()) {
            $this->get_node('arguments')->set_node('checkDefined', new Constant_Expression(true, $this->get_template_line()));
        }
        $this->compile_callable($compiler);
    }
}