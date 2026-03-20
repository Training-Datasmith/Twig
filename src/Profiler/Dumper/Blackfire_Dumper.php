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
final class Blackfire_Dumper
{
    public function dump(Profile $profile): string
    {
        $data = [];
        $this->dump_profile('main()', $profile, $data);
        $this->dump_children('main()', $profile, $data);
        $start = \sprintf('%f', microtime(true));
        $str = <<<EOF
        file-format: BlackfireProbe
        cost-dimensions: wt mu pmu
        request-start: {$start}
        
        
        EOF;
        foreach ($data as $name => $values) {
            $str .= "{$name}//{$values['ct']} {$values['wt']} {$values['mu']} {$values['pmu']}\n";
        }
        return $str;
    }
    private function dump_children(string $parent, Profile $profile, &$data): void
    {
        foreach ($profile as $p) {
            if ($p->is_template()) {
                $name = $p->get_template();
            } else {
                $name = \sprintf('%s::%s(%s)', $p->get_template(), $p->get_type(), $p->get_name());
            }
            $this->dump_profile(\sprintf('%s==>%s', $parent, $name), $p, $data);
            $this->dump_children($name, $p, $data);
        }
    }
    private function dump_profile(string $edge, Profile $profile, array &$data): void
    {
        if (isset($data[$edge])) {
            ++$data[$edge]['ct'];
            $data[$edge]['wt'] += floor($profile->get_duration() * 1000000);
            $data[$edge]['mu'] += $profile->get_memory_usage();
            $data[$edge]['pmu'] += $profile->get_peak_memory_usage();
        } else {
            $data[$edge] = ['ct' => 1, 'wt' => floor($profile->get_duration() * 1000000), 'mu' => $profile->get_memory_usage(), 'pmu' => $profile->get_peak_memory_usage()];
        }
    }
}