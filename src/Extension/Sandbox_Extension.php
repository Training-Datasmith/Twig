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
namespace Twig\Extension;

use Twig\Node_Visitor\Sandbox_Node_Visitor;
use Twig\Sandbox\Security_Not_Allowed_Constant_Error;
use Twig\Sandbox\Security_Not_Allowed_Method_Error;
use Twig\Sandbox\Security_Not_Allowed_Property_Error;
use Twig\Sandbox\Security_Policy_Interface;
use Twig\Sandbox\Source_Policy_Interface;
use Twig\Source;
use Twig\Token_Parser\Sandbox_Token_Parser;
final class Sandbox_Extension extends Abstract_Extension
{
    private int $sandbox_count = 0;
    public function __construct(private Security_Policy_Interface $policy, private $sandboxed_globally = false, private readonly ?Source_Policy_Interface $source_policy = null)
    {
    }
    public function get_token_parsers(): array
    {
        return [new Sandbox_Token_Parser()];
    }
    public function get_node_visitors(): array
    {
        return [new Sandbox_Node_Visitor()];
    }
    public function enable_sandbox(): void
    {
        ++$this->sandbox_count;
    }
    public function disable_sandbox(): void
    {
        if ($this->sandbox_count > 0) {
            --$this->sandbox_count;
        }
    }
    public function is_sandboxed(?Source $source = null): bool
    {
        return $this->sandboxed_globally || $this->sandbox_count > 0 || $this->is_source_sandboxed($source);
    }
    public function is_sandboxed_globally(): bool
    {
        return $this->sandboxed_globally;
    }
    private function is_source_sandboxed(?Source $source): bool
    {
        if (null === $source || null === $this->source_policy) {
            return false;
        }
        return $this->source_policy->enable_sandbox($source);
    }
    public function set_security_policy(Security_Policy_Interface $policy): void
    {
        $this->policy = $policy;
    }
    public function get_security_policy(): Security_Policy_Interface
    {
        return $this->policy;
    }
    public function check_security($tags, $filters, $functions, ?Source $source = null): void
    {
        if ($this->is_sandboxed($source)) {
            $this->policy->check_security($tags, $filters, $functions);
        }
    }
    public function check_method_allowed($obj, $method, int $lineno = -1, ?Source $source = null): void
    {
        if ($this->is_sandboxed($source)) {
            try {
                $this->policy->check_method_allowed($obj, $method);
            } catch (Security_Not_Allowed_Method_Error $e) {
                $e->set_source_context($source);
                $e->set_template_line($lineno);
                throw $e;
            }
        }
    }
    public function check_property_allowed($obj, $property, int $lineno = -1, ?Source $source = null): void
    {
        if ($this->is_sandboxed($source)) {
            try {
                $this->policy->check_property_allowed($obj, $property);
            } catch (Security_Not_Allowed_Property_Error $e) {
                $e->set_source_context($source);
                $e->set_template_line($lineno);
                throw $e;
            }
        }
    }
    public function check_constant_allowed(string $constant, int $lineno = -1, ?Source $source = null): void
    {
        if ($this->is_sandboxed($source)) {
            try {
                $this->policy->check_constant_allowed($constant);
            } catch (Security_Not_Allowed_Constant_Error $e) {
                $e->set_source_context($source);
                $e->set_template_line($lineno);
                throw $e;
            }
        }
    }
    /**
     * @throws SecurityNotAllowedMethodError
     */
    public function ensure_to_string_allowed($obj, int $lineno = -1, ?Source $source = null)
    {
        if (\is_array($obj)) {
            if ($this->is_sandboxed($source)) {
                $this->ensure_to_string_allowed_for_array($obj, $lineno, $source);
            }
            return $obj;
        }
        if ($obj instanceof \Stringable && $this->is_sandboxed($source)) {
            try {
                $this->policy->check_method_allowed($obj, '__toString');
            } catch (Security_Not_Allowed_Method_Error $e) {
                $e->set_source_context($source);
                $e->set_template_line($lineno);
                throw $e;
            }
        }
        return $obj;
    }
    private function ensure_to_string_allowed_for_array(array $obj, int $lineno, ?Source $source, array &$stack = []): void
    {
        foreach ($obj as $k => $v) {
            if (null === $v || \is_scalar($v)) {
                continue;
            }
            if (!\is_array($v)) {
                $this->ensure_to_string_allowed($v, $lineno, $source);
                continue;
            }
            if ($r = \Reflection_Reference::from_array_element($obj, $k)) {
                if (isset($stack[$r->get_id()])) {
                    continue;
                }
                $stack[$r->get_id()] = true;
            }
            $this->ensure_to_string_allowed_for_array($v, $lineno, $source, $stack);
        }
    }
}