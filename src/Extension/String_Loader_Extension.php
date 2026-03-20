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

use Twig\Environment;
use Twig\Template_Wrapper;
use Twig\Twig_Function;
final class String_Loader_Extension extends Abstract_Extension
{
    public function get_functions(): array
    {
        return [new Twig_Function('template_from_string', self::template_from_string(...), ['needs_environment' => true])];
    }
    /**
     * Loads a template from a string.
     *
     *     {{ include(template_from_string("Hello {{ name }}")) }}
     *
     * @param string|null $name An optional name of the template to be used in error messages
     *
     * @internal
     */
    public static function template_from_string(Environment $env, string|\Stringable $template, ?string $name = null): Template_Wrapper
    {
        return $env->create_template((string) $template, $name);
    }
}