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
 * Implements a cache on the filesystem.
 *
 * @author Andrew Tch <andrew@noop.lv>
 */
class Filesystem_Cache implements Cache_Interface, Removable_Cache_Interface
{
    public const FORCE_BYTECODE_INVALIDATION = 1;
    private readonly string $directory;
    public function __construct(string $directory, private readonly int $options = 0)
    {
        $this->directory = rtrim($directory, '\/') . '/';
    }
    public function generate_key(string $name, string $class_name): string
    {
        $hash = hash('xxh128', $class_name);
        return $this->directory . $hash[0] . $hash[1] . '/' . $hash . '.php';
    }
    public function load(string $key): void
    {
        if (is_file($key)) {
            @include_once $key;
        }
    }
    public function write(string $key, string $content): void
    {
        $dir = \dirname($key);
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true)) {
                clearstatcache(true, $dir);
                if (!is_dir($dir)) {
                    throw new \RuntimeException(\sprintf('Unable to create the cache directory (%s).', $dir));
                }
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(\sprintf('Unable to write in the cache directory (%s).', $dir));
        }
        $tmp_file = tempnam($dir, basename($key));
        if (false !== @file_put_contents($tmp_file, $content) && @rename($tmp_file, $key)) {
            @chmod($key, 0666 & ~umask());
            if (self::FORCE_BYTECODE_INVALIDATION === ($this->options & self::FORCE_BYTECODE_INVALIDATION)) {
                if (\function_exists('opcache_invalidate') && filter_var(\ini_get('opcache.enable'), \FILTER_VALIDATE_BOOLEAN)) {
                    @opcache_invalidate($key, true);
                }
            }
            return;
        }
        @unlink($tmp_file);
        throw new \RuntimeException(\sprintf('Failed to write cache file "%s".', $key));
    }
    public function remove(string $name, string $cls): void
    {
        $key = $this->generate_key($name, $cls);
        if (!@unlink($key) && file_exists($key)) {
            throw new \RuntimeException(\sprintf('Failed to delete cache file "%s".', $key));
        }
    }
    public function get_timestamp(string $key): int
    {
        if (!is_file($key)) {
            return 0;
        }
        return (int) @filemtime($key);
    }
}