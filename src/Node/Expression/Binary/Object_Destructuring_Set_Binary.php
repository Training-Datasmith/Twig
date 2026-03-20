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
namespace Twig\Node\Expression\Binary;

use Twig\Compiler;
use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Node\Node;
/**
 * @internal
 */
class Object_Destructuring_Set_Binary extends Abstract_Binary
{
    /** @var list<array{property: string, variable: string}> */
    private array $mappings = [];
    /**
     * @param ArrayExpression    $left  The array expression containing object/mapping destructuring properties
     * @param AbstractExpression $right The expression providing values for assignment
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        if (!$left instanceof Array_Expression) {
            throw new \LogicException('Left side must be ArrayExpression for object/mapping destructuring.');
        }
        foreach ($left->get_key_value_pairs() as $pair) {
            if (!$pair['value'] instanceof Context_Variable) {
                throw new Syntax_Error(\sprintf('Cannot assign to "%s", only variables can be assigned in object/mapping destructuring.', $pair['value']::class), $lineno);
            }
            $this->mappings[] = ['property' => $pair['key']->get_attribute('value'), 'variable' => $pair['value']->get_attribute('name')];
        }
        parent::__construct($left, $right, $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->add_debug_info($this);
        $compiler->raw('[');
        foreach ($this->mappings as $i => $mapping) {
            if ($i) {
                $compiler->raw(', ');
            }
            $compiler->raw('$context[')->repr($mapping['variable'])->raw(']');
        }
        $compiler->raw('] = [');
        foreach ($this->mappings as $i => $mapping) {
            if ($i) {
                $compiler->raw(', ');
            }
            $compiler->raw('CoreExtension::getAttribute($this->env, $this->source, ')->subcompile($this->get_node('right'))->raw(', ')->repr($mapping['property'])->raw(', [], \Twig\Template::ANY_CALL, false, false, false, ')->repr($this->get_node('right')->get_template_line())->raw(')');
        }
        $compiler->raw(']');
    }
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('=');
    }
}