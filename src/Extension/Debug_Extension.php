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
use Twig\Template;
use Twig\Template_Wrapper;
use Twig\Twig_Function;
final class Debug_Extension extends Abstract_Extension
{
    public function get_functions(): array
    {
        // dump is safe if var_dump is overridden by xdebug
        $is_dump_output_html_safe = \extension_loaded('xdebug') && str_contains(\ini_get('xdebug.mode'), 'develop') && (false === \ini_get('html_errors') || \ini_get('html_errors')) || 'cli' === \PHP_SAPI;
        return [new Twig_Function('dump', self::dump(...), ['is_safe' => $is_dump_output_html_safe ? ['html'] : [], 'needs_context' => true, 'needs_environment' => true, 'is_variadic' => true])];
    }
    /**
     * @internal
     */
    public static function dump(Environment $env, $context, ...$vars)
    {
        if (!$env->is_debug()) {
            return;
        }
        ob_start();
        if (!$vars) {
            $vars = [];
            foreach ($context as $key => $value) {
                if (!$value instanceof Template && !$value instanceof Template_Wrapper) {
                    $vars[$key] = $value;
                }
            }
            var_dump($vars);
        } else {
            var_dump(...$vars);
        }
        return ob_get_clean();
    }
}