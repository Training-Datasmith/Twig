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
namespace Twig\Cache;

/**
 * Chains several caches together.
 *
 * Cached items are fetched from the first cache having them in its data store.
 * They are saved and deleted in all adapters at once.
 *
 * @author Quentin Devos <quentin@devos.pm>
 */
final class Chain_Cache implements Cache_Interface, Removable_Cache_Interface
{
    /**
     * @param iterable<CacheInterface> $caches The ordered list of caches used to store and fetch cached items
     */
    public function __construct(private readonly iterable $caches)
    {
    }
    public function generate_key(string $name, string $class_name): string
    {
        return $class_name . "\x00" . $name;
    }
    public function write(string $key, string $content): void
    {
        $split_key = $this->split_key($key);
        foreach ($this->caches as $cache) {
            $cache->write($cache->generate_key(...$split_key), $content);
        }
    }
    public function load(string $key): void
    {
        [$name, $class_name] = $this->split_key($key);
        foreach ($this->caches as $cache) {
            $cache->load($cache->generate_key($name, $class_name));
            if (class_exists($class_name, false)) {
                break;
            }
        }
    }
    public function get_timestamp(string $key): int
    {
        $split_key = $this->split_key($key);
        foreach ($this->caches as $cache) {
            if (0 < $timestamp = $cache->get_timestamp($cache->generate_key(...$split_key))) {
                return $timestamp;
            }
        }
        return 0;
    }
    public function remove(string $name, string $cls): void
    {
        foreach ($this->caches as $cache) {
            if ($cache instanceof Removable_Cache_Interface) {
                $cache->remove($name, $cls);
            }
        }
    }
    /**
     * @return string[]
     */
    private function split_key(string $key): array
    {
        return array_reverse(explode("\x00", $key, 2));
    }
}