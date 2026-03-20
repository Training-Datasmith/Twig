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
final class Text_Dumper extends Base_Dumper
{
    protected function format_template(Profile $profile, $prefix): string
    {
        return \sprintf('%s└ %s', $prefix, $profile->get_template());
    }
    protected function format_non_template(Profile $profile, $prefix): string
    {
        return \sprintf('%s└ %s::%s(%s)', $prefix, $profile->get_template(), $profile->get_type(), $profile->get_name());
    }
    protected function format_time(Profile $profile, $percent): string
    {
        return \sprintf('%.2fms/%.0f%%', $profile->get_duration() * 1000, $percent);
    }
}