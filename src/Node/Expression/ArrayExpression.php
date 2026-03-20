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

use Twig\Compiler;
use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Unary\Spread_Unary;
use Twig\Node\Expression\Unary\String_Cast_Unary;
use Twig\Node\Expression\Variable\Context_Variable;
class Array_Expression extends Abstract_Expression implements Support_Defined_Test_Interface, Return_Array_Interface
{
    use Support_Defined_Test_Trait;
    private $index;
    public function __construct(array $elements, int $lineno)
    {
        parent::__construct($elements, [], $lineno);
        $this->index = -1;
        foreach ($this->get_key_value_pairs() as $pair) {
            if ($pair['key'] instanceof Constant_Expression && ctype_digit((string) $pair['key']->get_attribute('value')) && $pair['key']->get_attribute('value') > $this->index) {
                $this->index = $pair['key']->get_attribute('value');
            }
        }
    }
    public function get_key_value_pairs(): array
    {
        $pairs = [];
        foreach (array_chunk($this->nodes, 2) as $pair) {
            $pairs[] = ['key' => $pair[0], 'value' => $pair[1]];
        }
        return $pairs;
    }
    public function has_element(Abstract_Expression $key): bool
    {
        foreach ($this->get_key_value_pairs() as $pair) {
            // we compare the string representation of the keys
            // to avoid comparing the line numbers which are not relevant here.
            if ((string) $key === (string) $pair['key']) {
                return true;
            }
        }
        return false;
    }
    /**
     * Checks if the array is a sequence (keys are sequential integers starting from 0).
     *
     * @internal
     */
    public function is_sequence(): bool
    {
        foreach ($this->get_key_value_pairs() as $i => $pair) {
            $key = $pair['key'];
            if ($key instanceof Temp_Name_Expression) {
                $key_value = $key->get_attribute('name');
            } elseif ($key instanceof Constant_Expression) {
                $key_value = $key->get_attribute('value');
            } else {
                return false;
            }
            if ($key_value !== $i) {
                return false;
            }
        }
        return true;
    }
    public function add_element(Abstract_Expression $value, ?Abstract_Expression $key = null): void
    {
        if (null === $key) {
            $key = new Constant_Expression(++$this->index, $value->get_template_line());
        }
        array_push($this->nodes, $key, $value);
    }
    public function compile(Compiler $compiler): void
    {
        if ($this->defined_test) {
            $compiler->repr(true);
            return;
        }
        // Check for empty expressions which are only allowed in destructuring
        foreach ($this->get_key_value_pairs() as $pair) {
            if ($pair['value'] instanceof Empty_Expression) {
                throw new Syntax_Error('Empty array elements are only allowed in destructuring assignments.', $pair['value']->get_template_line(), $this->get_source_context());
            }
        }
        $compiler->raw('[');
        $is_sequence = true;
        foreach ($this->get_key_value_pairs() as $i => $pair) {
            if (0 !== $i) {
                $compiler->raw(', ');
            }
            $key = null;
            if ($pair['key'] instanceof Context_Variable) {
                $pair['key'] = new String_Cast_Unary($pair['key'], $pair['key']->get_template_line());
            } elseif ($pair['key'] instanceof Temp_Name_Expression) {
                $key = $pair['key']->get_attribute('name');
                $pair['key'] = new Constant_Expression($key, $pair['key']->get_template_line());
            } elseif ($pair['key'] instanceof Constant_Expression) {
                $key = $pair['key']->get_attribute('value');
            }
            if ($key !== $i) {
                $is_sequence = false;
            }
            if (!$is_sequence && !$pair['value'] instanceof Spread_Unary) {
                $compiler->subcompile($pair['key'])->raw(' => ');
            }
            $compiler->subcompile($pair['value']);
        }
        $compiler->raw(']');
    }
}