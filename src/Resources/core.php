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
use Twig\Extension\Core_Extension;
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_cycle($values, $position): mixed
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::cycle($values, $position);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_random(Environment $env, $values = null, $max = null)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::random($env->get_charset(), $values, $max);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_date_format_filter(Environment $env, $date, $format = null, $timezone = null): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return $env->get_extension(Core_Extension::class)->format_date($date, $format, $timezone);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_date_modify_filter(Environment $env, $date, $modifier)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return $env->get_extension(Core_Extension::class)->modify_date($date, $modifier);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_sprintf($format, ...$values): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::sprintf($format, ...$values);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_date_converter(Environment $env, $date = null, $timezone = null)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return $env->get_extension(Core_Extension::class)->convert_date($date, $timezone);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_replace_filter($str, $from): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::replace($str, $from);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_round($value, $precision = 0, $method = 'common')
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::round($value, $precision, $method);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_number_format_filter(Environment $env, $number, $decimal = null, $decimal_point = null, $thousand_sep = null): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return $env->get_extension(Core_Extension::class)->format_number($number, $decimal, $decimal_point, $thousand_sep);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_urlencode_filter($url): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::urlencode($url);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_array_merge(...$arrays): array
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::merge(...$arrays);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_slice(Environment $env, $item, $start, $length = null, $preserve_keys = false)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::slice($env->get_charset(), $item, $start, $length, $preserve_keys);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_first(Environment $env, $item)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::first($env->get_charset(), $item);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_last(Environment $env, $item)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::last($env->get_charset(), $item);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_join_filter($value, $glue = '', $and = null): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::join($value, $glue, $and);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_split_filter(Environment $env, $value, $delimiter, $limit = null): array
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::split($env->get_charset(), $value, $delimiter, $limit);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_get_array_keys_filter($array): array
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::keys($array);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_reverse_filter(Environment $env, $item, $preserve_keys = false)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::reverse($env->get_charset(), $item, $preserve_keys);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_sort_filter(Environment $env, $array, $arrow = null): array
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::sort($env, $array, $arrow);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_matches(string $regexp, ?string $str): int
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::matches($regexp, $str);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_trim_filter($string, $character_mask = null, $side = 'both'): string|\Stringable
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::trim($string, $character_mask, $side);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_nl2br($string): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::nl2br($string);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_spaceless($content): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::spaceless($content);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_convert_encoding($string, $to, $from): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::convert_encoding($string, $to, $from);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_length_filter(Environment $env, $thing): int
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::length($env->get_charset(), $thing);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_upper_filter(Environment $env, $string): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::upper($env->get_charset(), $string);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_lower_filter(Environment $env, $string): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::lower($env->get_charset(), $string);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_striptags($string, $allowable_tags = null): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::striptags($string, $allowable_tags);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_title_string_filter(Environment $env, $string): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::title_case($env->get_charset(), $string);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_capitalize_string_filter(Environment $env, $string): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::capitalize($env->get_charset(), $string);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_test_empty($value): bool
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::test_empty($value);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_test_iterable($value): bool
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return is_iterable($value);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_include(Environment $env, $context, $template, $variables = [], $with_context = true, $ignore_missing = false, $sandboxed = false): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::include($env, $context, $template, $variables, $with_context, $ignore_missing, $sandboxed);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_source(Environment $env, $name, $ignore_missing = false): string
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::source($env, $name, $ignore_missing);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_constant($constant, $object = null)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::constant($constant, $object);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_constant_is_defined($constant, $object = null)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::constant($constant, $object, true);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_array_batch($items, $size, $fill = null, $preserve_keys = true): array
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::batch($items, $size, $fill, $preserve_keys);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_array_column($array, $name, $index = null): array
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::column($array, $name, $index);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_array_filter(Environment $env, $array, $arrow)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::filter($env, $array, $arrow);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_array_map(Environment $env, $array, $arrow)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::map($env, $array, $arrow);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_array_reduce(Environment $env, $array, $arrow, $initial = null)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::reduce($env, $array, $arrow, $initial);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_array_some(Environment $env, $array, $arrow)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::array_some($env, $array, $arrow);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_array_every(Environment $env, $array, $arrow)
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return Core_Extension::array_every($env, $array, $arrow);
}
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_check_arrow_in_sandbox(Environment $env, $arrow, $thing, $type): void
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    Core_Extension::check_arrow($env, $arrow, $thing, $type);
}