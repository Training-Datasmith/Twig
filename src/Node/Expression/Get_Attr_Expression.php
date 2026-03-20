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
use Twig\Extension\Sandbox_Extension;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Template;
class Get_Attr_Expression extends Abstract_Expression implements Support_Defined_Test_Interface
{
    use Support_Defined_Test_Deprecation_Trait;
    use Support_Defined_Test_Trait;
    /**
     * @param ArrayExpression|NameExpression|null $arguments
     */
    public function __construct(Abstract_Expression $node, Abstract_Expression $attribute, ?Abstract_Expression $arguments, string $type, int $lineno, bool $null_safe = false)
    {
        $nodes = ['node' => $node, 'attribute' => $attribute];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }
        if ($arguments && !$arguments instanceof Array_Expression && !$arguments instanceof Context_Variable) {
            trigger_deprecation('twig/twig', '3.15', \sprintf('Not passing a "%s" instance as the "arguments" argument of the "%s" constructor is deprecated ("%s" given).', Array_Expression::class, static::class, $arguments::class));
        }
        parent::__construct($nodes, ['type' => $type, 'ignore_strict_check' => false, 'optimizable' => !$null_safe, 'null_safe' => $null_safe, 'is_short_circuited' => false, 'var_name' => null], $lineno);
    }
    public function enable_defined_test(): void
    {
        $this->defined_test = true;
        $this->change_ignore_strict_check($this);
    }
    public function compile(Compiler $compiler): void
    {
        $env = $compiler->get_environment();
        $array_access_sandbox = false;
        $null_safe = $this->get_attribute('null_safe');
        // optimize array calls
        if ($this->get_attribute('optimizable') && (!$env->is_strict_variables() || $this->get_attribute('ignore_strict_check')) && !$this->defined_test && Template::ARRAY_CALL === $this->get_attribute('type')) {
            $var = '$' . $compiler->get_var_name();
            $compiler->raw('((' . $var . ' = ')->subcompile($this->get_node('node'))->raw(') && is_array(')->raw($var);
            if (!$env->has_extension(Sandbox_Extension::class)) {
                $compiler->raw(') || ')->raw($var)->raw(' instanceof ArrayAccess ? (')->raw($var)->raw('[')->subcompile($this->get_node('attribute'))->raw('] ?? null) : null)');
                return;
            }
            $array_access_sandbox = true;
            $compiler->raw(') || ')->raw($var)->raw(' instanceof ArrayAccess && in_array(')->raw($var . '::class')->raw(', CoreExtension::ARRAY_LIKE_CLASSES, true) ? (')->raw($var)->raw('[')->subcompile($this->get_node('attribute'))->raw('] ?? null) : ');
        }
        if ($this->get_attribute('ignore_strict_check')) {
            $this->get_node('node')->set_attribute('ignore_strict_check', true);
        }
        if (null === $null_safe_node = $null_safe ? $this : null) {
            $node = $this->get_node('node');
            while ($node instanceof self) {
                if ($node->get_attribute('null_safe')) {
                    $null_safe_node = $node;
                    break;
                }
                $node = $node->get_node('node');
            }
        }
        $is_short_circuited = false;
        if (null !== $null_safe_node && !$null_safe_node->is_short_circuited()) {
            $compiler->raw('((null === (' . $null_safe_node->get_var_name($compiler) . ' = ')->subcompile($null_safe_node->get_node('node'))->raw(')) ? null : ');
            $null_safe_node->mark_as_short_circuited();
            $is_short_circuited = true;
        }
        $compiler->raw('CoreExtension::getAttribute($this->env, $this->source, ');
        if ($null_safe) {
            $compiler->raw($this->get_var_name($compiler));
        } else {
            $compiler->subcompile($this->get_node('node'));
        }
        $compiler->raw(', ')->subcompile($this->get_node('attribute'));
        if ($this->has_node('arguments')) {
            $compiler->raw(', ')->subcompile($this->get_node('arguments'));
        } else {
            $compiler->raw(', []');
        }
        $compiler->raw(', ')->repr($this->get_attribute('type'))->raw(', ')->repr($this->defined_test)->raw(', ')->repr($this->get_attribute('ignore_strict_check'))->raw(', ')->repr($env->has_extension(Sandbox_Extension::class))->raw(', ')->repr($this->get_node('node')->get_template_line())->raw(')');
        if ($array_access_sandbox) {
            $compiler->raw(')');
        }
        if ($is_short_circuited) {
            $compiler->raw(')');
        }
    }
    private function change_ignore_strict_check(self $node): void
    {
        $node->set_attribute('optimizable', false);
        $node->set_attribute('ignore_strict_check', true);
        if ($node->get_node('node') instanceof self) {
            $this->change_ignore_strict_check($node->get_node('node'));
        }
    }
    private function mark_as_short_circuited(): void
    {
        $this->set_attribute('is_short_circuited', true);
    }
    private function is_short_circuited(): bool
    {
        return $this->get_attribute('is_short_circuited');
    }
    private function get_var_name(Compiler $compiler): string
    {
        if (null === $this->get_attribute('var_name')) {
            $this->set_attribute('var_name', $compiler->get_var_name());
        }
        return '$' . $this->get_attribute('var_name');
    }
}