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
use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Variable\Local_Variable;
/**
 * Represents a macro node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[Yield_Ready]
class Macro_Node extends Node
{
    public const VARARGS_NAME = 'varargs';
    /**
     * @param BodyNode        $body
     * @param ArrayExpression $arguments
     */
    public function __construct(string $name, Node $body, Node $arguments, int $lineno)
    {
        if (!$body instanceof Body_Node) {
            trigger_deprecation('twig/twig', '3.12', \sprintf('Not passing a "%s" instance as the "body" argument of the "%s" constructor is deprecated ("%s" given).', Body_Node::class, static::class, $body::class));
        }
        if (!$arguments instanceof Array_Expression) {
            trigger_deprecation('twig/twig', '3.15', \sprintf('Not passing a "%s" instance as the "arguments" argument of the "%s" constructor is deprecated ("%s" given).', Array_Expression::class, static::class, $arguments::class));
            $args = new Array_Expression([], $arguments->get_template_line());
            foreach ($arguments as $n => $default) {
                $args->add_element($default, new Local_Variable($n, $default->get_template_line()));
            }
            $arguments = $args;
        }
        foreach ($arguments->get_key_value_pairs() as $pair) {
            if ("͜" . self::VARARGS_NAME === $pair['key']->get_attribute('name')) {
                throw new Syntax_Error(\sprintf('The argument "%s" in macro "%s" cannot be defined because the variable "%s" is reserved for arbitrary arguments.', self::VARARGS_NAME, $name, self::VARARGS_NAME), $pair['value']->get_template_line(), $pair['value']->get_source_context());
            }
        }
        parent::__construct(['body' => $body, 'arguments' => $arguments], ['name' => $name], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this)->write(\sprintf('public function macro_%s(', $this->get_attribute('name')));
        /** @var ArrayExpression $arguments */
        $arguments = $this->get_node('arguments');
        foreach ($arguments->get_key_value_pairs() as $pair) {
            $name = $pair['key'];
            $default = $pair['value'];
            $compiler->subcompile($name)->raw(' = ')->subcompile($default)->raw(', ');
        }
        $compiler->raw('...$varargs')->raw("): string|Markup\n")->write("{\n")->indent()->write("\$macros = \$this->macros;\n")->write("\$context = [\n")->indent();
        foreach ($arguments->get_key_value_pairs() as $pair) {
            $name = $pair['key'];
            $var = $name->get_attribute('name');
            if (str_starts_with((string) $var, "͜")) {
                $var = substr((string) $var, \strlen("͜"));
            }
            $compiler->write('')->string($var)->raw(' => ')->subcompile($name)->raw(",\n");
        }
        $node = new Capture_Node($this->get_node('body'), $this->get_node('body')->lineno);
        $compiler->write('')->string(self::VARARGS_NAME)->raw(' => ')->raw("\$varargs,\n")->outdent()->write("] + \$this->env->getGlobals();\n\n")->write("\$blocks = [];\n\n")->write('return ')->subcompile($node)->raw("\n")->outdent()->write("}\n\n");
    }
}