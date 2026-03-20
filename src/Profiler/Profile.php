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
namespace Twig\Profiler;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Profile implements \IteratorAggregate
{
    public const ROOT = 'ROOT';
    public const BLOCK = 'block';
    public const TEMPLATE = 'template';
    public const MACRO = 'macro';
    private array $starts = [];
    private array $ends = [];
    private array $profiles = [];
    public function __construct(private string $template = 'main', private string $type = self::ROOT, private string $name = 'main')
    {
        $this->name = str_starts_with($name, '__internal_') ? 'INTERNAL' : $name;
        $this->enter();
    }
    public function get_template(): string
    {
        return $this->template;
    }
    public function get_type(): string
    {
        return $this->type;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function is_root(): bool
    {
        return self::ROOT === $this->type;
    }
    public function is_template(): bool
    {
        return self::TEMPLATE === $this->type;
    }
    public function is_block(): bool
    {
        return self::BLOCK === $this->type;
    }
    public function is_macro(): bool
    {
        return self::MACRO === $this->type;
    }
    /**
     * @return Profile[]
     */
    public function get_profiles(): array
    {
        return $this->profiles;
    }
    public function add_profile(self $profile): void
    {
        $this->profiles[] = $profile;
    }
    /**
     * Returns the duration in microseconds.
     */
    public function get_duration(): float
    {
        if ($this->is_root() && $this->profiles) {
            // for the root node with children, duration is the sum of all child durations
            $duration = 0;
            foreach ($this->profiles as $profile) {
                $duration += $profile->get_duration();
            }
            return $duration;
        }
        return isset($this->ends['wt']) && isset($this->starts['wt']) ? $this->ends['wt'] - $this->starts['wt'] : 0;
    }
    /**
     * Returns the start time in microseconds.
     */
    public function get_start_time(): float
    {
        return $this->starts['wt'] ?? 0.0;
    }
    /**
     * Returns the end time in microseconds.
     */
    public function get_end_time(): float
    {
        return $this->ends['wt'] ?? 0.0;
    }
    /**
     * Returns the memory usage in bytes.
     */
    public function get_memory_usage(): int
    {
        return isset($this->ends['mu']) && isset($this->starts['mu']) ? $this->ends['mu'] - $this->starts['mu'] : 0;
    }
    /**
     * Returns the peak memory usage in bytes.
     */
    public function get_peak_memory_usage(): int
    {
        return isset($this->ends['pmu']) && isset($this->starts['pmu']) ? $this->ends['pmu'] - $this->starts['pmu'] : 0;
    }
    /**
     * Starts the profiling.
     */
    public function enter(): void
    {
        $this->starts = ['wt' => microtime(true), 'mu' => memory_get_usage(), 'pmu' => memory_get_peak_usage()];
    }
    /**
     * Stops the profiling.
     */
    public function leave(): void
    {
        $this->ends = ['wt' => microtime(true), 'mu' => memory_get_usage(), 'pmu' => memory_get_peak_usage()];
    }
    public function reset(): void
    {
        $this->starts = $this->ends = $this->profiles = [];
        $this->enter();
    }
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->profiles);
    }
    /**
     * @internal
     */
    public function __serialize(): array
    {
        return [$this->template, $this->name, $this->type, $this->starts, $this->ends, $this->profiles];
    }
    /**
     * @internal
     */
    public function __unserialize(array $data): void
    {
        [$this->template, $this->name, $this->type, $this->starts, $this->ends, $this->profiles] = $data;
    }
}