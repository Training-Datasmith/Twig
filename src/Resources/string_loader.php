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
use Twig\Environment;
use Twig\Extension\String_Loader_Extension;
use Twig\Template_Wrapper;
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_template_from_string(Environment $env, string|\Stringable $template, ?string $name = null): Template_Wrapper
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return String_Loader_Extension::template_from_string($env, $template, $name);
}