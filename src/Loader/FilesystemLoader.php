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
 * Loads template from the filesystem.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Filesystem_Loader implements Loader_Interface
{
    /** Identifier of the main namespace. */
    public const MAIN_NAMESPACE = '__main__';
    /**
     * @var array<string, list<string>>
     */
    protected $paths = [];
    protected $cache = [];
    protected $error_cache = [];
    private string $root_path;
    /**
     * @param string|string[] $paths    A path or an array of paths where to look for templates
     * @param string|null     $rootPath The root path common to all relative paths (null for getcwd())
     */
    public function __construct($paths = [], ?string $root_path = null)
    {
        $this->root_path = ($root_path ?? getcwd()) . \DIRECTORY_SEPARATOR;
        if (null !== $root_path && false !== $real_path = realpath($root_path)) {
            $this->root_path = $real_path . \DIRECTORY_SEPARATOR;
        }
        if ($paths) {
            $this->set_paths($paths);
        }
    }
    /**
     * Returns the paths to the templates.
     *
     * @return list<string>
     */
    public function get_paths(string $namespace = self::MAIN_NAMESPACE): array
    {
        return $this->paths[$namespace] ?? [];
    }
    /**
     * Returns the path namespaces.
     *
     * The main namespace is always defined.
     *
     * @return list<string>
     */
    public function get_namespaces(): array
    {
        return array_keys($this->paths);
    }
    /**
     * @param string|string[] $paths A path or an array of paths where to look for templates
     */
    public function set_paths($paths, string $namespace = self::MAIN_NAMESPACE): void
    {
        if (!\is_array($paths)) {
            $paths = [$paths];
        }
        $this->paths[$namespace] = [];
        foreach ($paths as $path) {
            $this->add_path($path, $namespace);
        }
    }
    /**
     * @throws LoaderError
     */
    public function add_path(string $path, string $namespace = self::MAIN_NAMESPACE): void
    {
        // invalidate the cache
        $this->cache = $this->error_cache = [];
        $check_path = $this->is_absolute_path($path) ? $path : $this->root_path . $path;
        if (!is_dir($check_path)) {
            throw new Loader_Error(\sprintf('The "%s" directory does not exist ("%s").', $path, $check_path));
        }
        $this->paths[$namespace][] = rtrim($path, '/\\');
    }
    /**
     * @throws LoaderError
     */
    public function prepend_path(string $path, string $namespace = self::MAIN_NAMESPACE): void
    {
        // invalidate the cache
        $this->cache = $this->error_cache = [];
        $check_path = $this->is_absolute_path($path) ? $path : $this->root_path . $path;
        if (!is_dir($check_path)) {
            throw new Loader_Error(\sprintf('The "%s" directory does not exist ("%s").', $path, $check_path));
        }
        $path = rtrim($path, '/\\');
        if (!isset($this->paths[$namespace])) {
            $this->paths[$namespace][] = $path;
        } else {
            array_unshift($this->paths[$namespace], $path);
        }
    }
    public function get_source_context(string $name): Source
    {
        if (null === $path = $this->find_template($name)) {
            return new Source('', $name, '');
        }
        return new Source(file_get_contents($path), $name, $path);
    }
    public function get_cache_key(string $name): string
    {
        if (null === $path = $this->find_template($name)) {
            return '';
        }
        $len = \strlen($this->root_path);
        if (0 === strncmp($this->root_path, $path, $len)) {
            return substr($path, $len);
        }
        return $path;
    }
    /**
     * @return bool
     */
    public function exists(string $name)
    {
        $name = $this->normalize_name($name);
        if (isset($this->cache[$name])) {
            return true;
        }
        return null !== $this->find_template($name, false);
    }
    public function is_fresh(string $name, int $time): bool
    {
        // false support to be removed in 3.0
        if (null === $path = $this->find_template($name)) {
            return false;
        }
        return filemtime($path) < $time;
    }
    /**
     * @return string|null
     */
    protected function find_template(string $name, bool $throw = true)
    {
        $name = $this->normalize_name($name);
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }
        if (isset($this->error_cache[$name])) {
            if (!$throw) {
                return null;
            }
            throw new Loader_Error($this->error_cache[$name]);
        }
        try {
            [$namespace, $shortname] = $this->parse_name($name);
            $this->validate_name($shortname);
        } catch (Loader_Error $e) {
            if (!$throw) {
                return null;
            }
            throw $e;
        }
        if (!isset($this->paths[$namespace])) {
            $this->error_cache[$name] = \sprintf('There are no registered paths for namespace "%s".', $namespace);
            if (!$throw) {
                return null;
            }
            throw new Loader_Error($this->error_cache[$name]);
        }
        foreach ($this->paths[$namespace] as $path) {
            if (!$this->is_absolute_path($path)) {
                $path = $this->root_path . $path;
            }
            if (is_file($path . '/' . $shortname)) {
                if (false !== $realpath = realpath($path . '/' . $shortname)) {
                    return $this->cache[$name] = $realpath;
                }
                return $this->cache[$name] = $path . '/' . $shortname;
            }
        }
        $this->error_cache[$name] = \sprintf('Unable to find template "%s" (looked into: %s).', $name, implode(', ', $this->paths[$namespace]));
        if (!$throw) {
            return null;
        }
        throw new Loader_Error($this->error_cache[$name]);
    }
    private function normalize_name(string $name): string
    {
        return preg_replace('#/{2,}#', '/', str_replace('\\', '/', $name));
    }
    private function parse_name(string $name, string $default = self::MAIN_NAMESPACE): array
    {
        if (isset($name[0]) && '@' == $name[0]) {
            if (false === $pos = strpos($name, '/')) {
                throw new Loader_Error(\sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));
            }
            $namespace = substr($name, 1, $pos - 1);
            $shortname = substr($name, $pos + 1);
            return [$namespace, $shortname];
        }
        return [$default, $name];
    }
    private function validate_name(string $name): void
    {
        if (str_contains($name, "\x00")) {
            throw new Loader_Error('A template name cannot contain NUL bytes.');
        }
        $name = ltrim($name, '/');
        $parts = explode('/', $name);
        $level = 0;
        foreach ($parts as $part) {
            if ('..' === $part) {
                --$level;
            } elseif ('.' !== $part) {
                ++$level;
            }
            if ($level < 0) {
                throw new Loader_Error(\sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
            }
        }
    }
    private function is_absolute_path(string $file): bool
    {
        return strspn($file, '/\\', 0, 1) || \strlen($file) > 3 && ctype_alpha($file[0]) && ':' === $file[1] && strspn($file, '/\\', 2, 1) || null !== parse_url($file, \PHP_URL_SCHEME);
    }
}