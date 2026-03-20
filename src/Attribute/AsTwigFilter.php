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
namespace Twig\Attribute;

use Twig\Deprecated_Callable_Info;
use Twig\Twig_Filter;
/**
 * Registers a method as template filter.
 *
 * If the first argument of the method has Twig\Environment type-hint, the filter will receive the current environment.
 * Additional arguments of the method come from the filter call.
 *
 *     #[AsTwigFilter(name: 'foo')]
 *     function fooFilter(Environment $env, $string, $arg1 = null, ...) { ... }
 *
 *    {{ 'string'|foo(arg1) }}
 *
 * @see TwigFilter
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class As_Twig_Filter
{
    /**
     * @param non-empty-string            $name             The name of the filter in Twig
     * @param bool|null                   $needsCharset     Whether the filter needs the charset passed as the first argument
     * @param bool|null                   $needsEnvironment Whether the filter needs the environment passed as the first argument, or after the charset
     * @param bool|null                   $needsContext     Whether the filter needs the context array passed as the first argument, or after the charset and the environment
     * @param string[]|null               $isSafe           List of formats in which you want the raw output to be printed unescaped
     * @param string|array|null           $isSafeCallback   Function called at compilation time to determine if the filter is safe
     * @param string|null                 $preEscape        Some filters may need to work on input that is already escaped or safe
     * @param string[]|null               $preservesSafety  Preserves the safety of the value that the filter is applied to
     * @param DeprecatedCallableInfo|null $deprecationInfo  Information about the deprecation
     */
    public function __construct(public string $name, public ?bool $needs_charset = null, public ?bool $needs_environment = null, public ?bool $needs_context = null, public ?array $is_safe = null, public string|array|null $is_safe_callback = null, public ?string $pre_escape = null, public ?array $preserves_safety = null, public ?Deprecated_Callable_Info $deprecation_info = null)
    {
    }
}