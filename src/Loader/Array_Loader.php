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
 * Loads a template from an array.
 *
 * When using this loader with a cache mechanism, you should know that a new cache
 * key is generated each time a template content "changes" (the cache key being the
 * source code of the template). If you don't want to see your cache grows out of
 * control, you need to take care of clearing the old cache file by yourself.
 *
 * This loader should only be used for unit testing.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Array_Loader implements Loader_Interface
{
    /**
     * @param array $templates An array of templates (keys are the names, and values are the source code)
     */
    public function __construct(private array $templates = [])
    {
    }
    public function set_template(string $name, string $template): void
    {
        $this->templates[$name] = $template;
    }
    public function get_source_context(string $name): Source
    {
        if (!isset($this->templates[$name])) {
            throw new Loader_Error(\sprintf('Template "%s" is not defined.', $name));
        }
        return new Source((string) $this->templates[$name], $name);
    }
    public function exists(string $name): bool
    {
        return isset($this->templates[$name]);
    }
    public function get_cache_key(string $name): string
    {
        if (!isset($this->templates[$name])) {
            throw new Loader_Error(\sprintf('Template "%s" is not defined.', $name));
        }
        return $name . ':' . $this->templates[$name];
    }
    public function is_fresh(string $name, int $time): bool
    {
        if (!isset($this->templates[$name])) {
            throw new Loader_Error(\sprintf('Template "%s" is not defined.', $name));
        }
        return true;
    }
}