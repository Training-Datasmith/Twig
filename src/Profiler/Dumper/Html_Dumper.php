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
namespace Twig\Profiler\Dumper;

use Twig\Profiler\Profile;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Html_Dumper extends Base_Dumper
{
    private static array $colors = ['block' => '#dfd', 'macro' => '#ddf', 'template' => '#ffd', 'big' => '#d44'];
    public function dump(Profile $profile): string
    {
        return '<pre>' . parent::dump($profile) . '</pre>';
    }
    protected function format_template(Profile $profile, $prefix): string
    {
        return \sprintf('%s└ <span style="background-color: %s">%s</span>', $prefix, self::$colors['template'], $profile->get_template());
    }
    protected function format_non_template(Profile $profile, $prefix): string
    {
        return \sprintf('%s└ %s::%s(<span style="background-color: %s">%s</span>)', $prefix, $profile->get_template(), $profile->get_type(), self::$colors[$profile->get_type()] ?? 'auto', $profile->get_name());
    }
    protected function format_time(Profile $profile, $percent): string
    {
        return \sprintf('<span style="color: %s">%.2fms/%.0f%%</span>', $percent > 20 ? self::$colors['big'] : 'auto', $profile->get_duration() * 1000, $percent);
    }
}