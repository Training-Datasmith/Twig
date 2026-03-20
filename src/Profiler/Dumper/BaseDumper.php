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
abstract class Base_Dumper
{
    private ?float $root = null;
    public function dump(Profile $profile): string
    {
        return $this->dump_profile($profile);
    }
    abstract protected function format_template(Profile $profile, $prefix): string;
    abstract protected function format_non_template(Profile $profile, $prefix): string;
    abstract protected function format_time(Profile $profile, $percent): string;
    private function dump_profile(Profile $profile, string $prefix = '', bool $sibling = false): string
    {
        if ($profile->is_root()) {
            $this->root = $profile->get_duration();
            $start = $profile->get_name();
        } else {
            if ($profile->is_template()) {
                $start = $this->format_template($profile, $prefix);
            } else {
                $start = $this->format_non_template($profile, $prefix);
            }
            $prefix .= $sibling ? '│ ' : '  ';
        }
        $percent = $this->root ? $profile->get_duration() / $this->root * 100 : 0;
        if ($profile->get_duration() * 1000 < 1) {
            $str = $start . "\n";
        } else {
            $str = \sprintf("%s %s\n", $start, $this->format_time($profile, $percent));
        }
        $n_count = \count($profile->get_profiles());
        foreach ($profile as $i => $p) {
            $str .= $this->dump_profile($p, $prefix, $i + 1 !== $n_count);
        }
        return $str;
    }
}