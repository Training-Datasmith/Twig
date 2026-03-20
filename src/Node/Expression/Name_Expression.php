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
use Twig\Node\Expression\Variable\Context_Variable;
class Name_Expression extends Abstract_Expression implements Support_Defined_Test_Interface
{
    use Support_Defined_Test_Deprecation_Trait;
    use Support_Defined_Test_Trait;
    private array $special_vars = ['_self' => '$this->getTemplateName()', '_context' => '$context', '_charset' => '$this->env->getCharset()'];
    public function __construct(string $name, int $lineno)
    {
        if (self::class === static::class) {
            trigger_deprecation('twig/twig', '3.15', 'The "%s" class is deprecated, use "%s" instead.', self::class, Context_Variable::class);
        }
        parent::__construct([], ['name' => $name, 'ignore_strict_check' => false, 'always_defined' => false], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $name = $this->get_attribute('name');
        $compiler->add_debug_info($this);
        if ($this->defined_test) {
            if (isset($this->special_vars[$name]) || $this->get_attribute('always_defined')) {
                $compiler->repr(true);
            } elseif (\PHP_VERSION_ID >= 70400) {
                $compiler->raw('array_key_exists(')->string($name)->raw(', $context)');
            } else {
                $compiler->raw('(isset($context[')->string($name)->raw(']) || array_key_exists(')->string($name)->raw(', $context))');
            }
        } elseif (isset($this->special_vars[$name])) {
            $compiler->raw($this->special_vars[$name]);
        } elseif ($this->get_attribute('always_defined')) {
            $compiler->raw('$context[')->string($name)->raw(']');
        } else if ($this->get_attribute('ignore_strict_check') || !$compiler->get_environment()->is_strict_variables()) {
            $compiler->raw('($context[')->string($name)->raw('] ?? null)');
        } else {
            $compiler->raw('(isset($context[')->string($name)->raw(']) || array_key_exists(')->string($name)->raw(', $context) ? $context[')->string($name)->raw('] : (function () { throw new RuntimeError(\'Variable ')->string($name)->raw(' does not exist.\', ')->repr($this->lineno)->raw(', $this->source); })()')->raw(')');
        }
    }
    /**
     * @deprecated since Twig 3.11 (to be removed in 4.0)
     */
    public function is_special(): bool
    {
        trigger_deprecation('twig/twig', '3.11', 'The "%s()" method is deprecated and will be removed in Twig 4.0.', __METHOD__);
        return isset($this->special_vars[$this->get_attribute('name')]);
    }
    /**
     * @deprecated since Twig 3.11 (to be removed in 4.0)
     */
    public function is_simple(): bool
    {
        trigger_deprecation('twig/twig', '3.11', 'The "%s()" method is deprecated and will be removed in Twig 4.0.', __METHOD__);
        return !isset($this->special_vars[$this->get_attribute('name')]) && !$this->defined_test;
    }
}