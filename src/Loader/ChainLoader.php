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
namespace Twig\Loader;

use Twig\Error\Loader_Error;
use Twig\Source;
/**
 * Loads templates from other loaders.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Chain_Loader implements Loader_Interface
{
    /**
     * @var array<string, bool>
     */
    private array $has_source_cache = [];
    /**
     * @param iterable<LoaderInterface> $loaders
     */
    public function __construct(private iterable $loaders = [])
    {
    }
    public function add_loader(Loader_Interface $loader): void
    {
        $current = $this->loaders;
        $this->loaders = (static function () use ($current, $loader): \Generator {
            yield from $current;
            yield $loader;
        })();
        $this->has_source_cache = [];
    }
    /**
     * @return LoaderInterface[]
     */
    public function get_loaders(): array
    {
        if (!\is_array($this->loaders)) {
            $this->loaders = iterator_to_array($this->loaders, false);
        }
        return $this->loaders;
    }
    public function get_source_context(string $name): Source
    {
        $exceptions = [];
        foreach ($this->get_loaders() as $loader) {
            if (!$loader->exists($name)) {
                continue;
            }
            try {
                return $loader->get_source_context($name);
            } catch (Loader_Error $e) {
                $exceptions[] = $e->get_message();
            }
        }
        throw new Loader_Error(\sprintf('Template "%s" is not defined%s.', $name, $exceptions ? ' (' . implode(', ', $exceptions) . ')' : ''));
    }
    public function exists(string $name): bool
    {
        if (isset($this->has_source_cache[$name])) {
            return $this->has_source_cache[$name];
        }
        foreach ($this->get_loaders() as $loader) {
            if ($loader->exists($name)) {
                return $this->has_source_cache[$name] = true;
            }
        }
        return $this->has_source_cache[$name] = false;
    }
    public function get_cache_key(string $name): string
    {
        $exceptions = [];
        foreach ($this->get_loaders() as $loader) {
            if (!$loader->exists($name)) {
                continue;
            }
            try {
                return $loader->get_cache_key($name);
            } catch (Loader_Error $e) {
                $exceptions[] = $loader::class . ': ' . $e->get_message();
            }
        }
        throw new Loader_Error(\sprintf('Template "%s" is not defined%s.', $name, $exceptions ? ' (' . implode(', ', $exceptions) . ')' : ''));
    }
    public function is_fresh(string $name, int $time): bool
    {
        $exceptions = [];
        foreach ($this->get_loaders() as $loader) {
            if (!$loader->exists($name)) {
                continue;
            }
            try {
                return $loader->is_fresh($name, $time);
            } catch (Loader_Error $e) {
                $exceptions[] = $loader::class . ': ' . $e->get_message();
            }
        }
        throw new Loader_Error(\sprintf('Template "%s" is not defined%s.', $name, $exceptions ? ' (' . implode(', ', $exceptions) . ')' : ''));
    }
}