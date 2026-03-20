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
namespace Twig;

use Twig\Cache\Cache_Interface;
use Twig\Cache\Filesystem_Cache;
use Twig\Cache\Null_Cache;
use Twig\Cache\Removable_Cache_Interface;
use Twig\Error\Error;
use Twig\Error\Loader_Error;
use Twig\Error\Runtime_Error;
use Twig\Error\Syntax_Error;
use Twig\Expression_Parser\Expression_Parsers;
use Twig\Extension\Core_Extension;
use Twig\Extension\Escaper_Extension;
use Twig\Extension\Extension_Interface;
use Twig\Extension\Optimizer_Extension;
use Twig\Extension\Yield_Not_Ready_Extension;
use Twig\Loader\Array_Loader;
use Twig\Loader\Chain_Loader;
use Twig\Loader\Loader_Interface;
use Twig\Node\Module_Node;
use Twig\Node\Node;
use Twig\Node_Visitor\Node_Visitor_Interface;
use Twig\Runtime\Escaper_Runtime;
use Twig\Runtime_Loader\Factory_Runtime_Loader;
use Twig\Runtime_Loader\Runtime_Loader_Interface;
use Twig\Token_Parser\Token_Parser_Interface;
/**
 * Stores the Twig configuration and renders templates.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Environment
{
    public const VERSION = '3.24.0-DEV';
    public const VERSION_ID = 32400;
    public const MAJOR_VERSION = 3;
    public const MINOR_VERSION = 24;
    public const RELEASE_VERSION = 0;
    public const EXTRA_VERSION = 'DEV';
    private $charset;
    private \Twig\Loader\Loader_Interface $loader;
    private bool $debug;
    private $auto_reload;
    private \Twig\Cache\Filesystem_Cache|\Twig\Cache\Null_Cache|\Twig\Cache\Cache_Interface|null $cache = null;
    private ?\Twig\Lexer $lexer = null;
    private ?\Twig\Parser $parser = null;
    private ?\Twig\Compiler $compiler = null;
    /** @var array<string, mixed> */
    private array $globals = [];
    private ?array $resolved_globals = null;
    private ?array $loaded_templates = null;
    private bool $strict_variables;
    private string|bool|\Twig\Cache\Cache_Interface|null $original_cache = null;
    private readonly \Twig\Extension_Set $extension_set;
    private array $runtime_loaders = [];
    private $runtimes = [];
    private ?string $options_hash = null;
    private readonly bool $use_yield;
    private readonly \Twig\Runtime_Loader\Factory_Runtime_Loader $default_runtime_loader;
    private array $hot_cache = [];
    /**
     * Constructor.
     *
     * Available options:
     *
     *  * debug: When set to true, it automatically set "auto_reload" to true as
     *           well (default to false).
     *
     *  * charset: The charset used by the templates (default to UTF-8).
     *
     *  * cache: An absolute path where to store the compiled templates,
     *           a \Twig\Cache\CacheInterface implementation,
     *           or false to disable compilation cache (default).
     *
     *  * auto_reload: Whether to reload the template if the original source changed.
     *                 If you don't provide the auto_reload option, it will be
     *                 determined automatically based on the debug value.
     *
     *  * strict_variables: Whether to ignore invalid variables in templates
     *                      (default to false).
     *
     *  * autoescape: Whether to enable auto-escaping (default to html):
     *                  * false: disable auto-escaping
     *                  * html, js: set the autoescaping to one of the supported strategies
     *                  * name: set the autoescaping strategy based on the template name extension
     *                  * PHP callback: a PHP callback that returns an escaping strategy based on the template "name"
     *
     *  * optimizations: A flag that indicates which optimizations to apply
     *                   (default to -1 which means that all optimizations are enabled;
     *                   set it to 0 to disable).
     *
     *  * use_yield: true: forces templates to exclusively use "yield" instead of "echo" (all extensions must be yield ready)
     *               false (default): allows templates to use a mix of "yield" and "echo" calls to allow for a progressive migration
     *               Switch to "true" when possible as this will be the only supported mode in Twig 4.0
     */
    public function __construct(Loader_Interface $loader, array $options = [])
    {
        $this->set_loader($loader);
        $options = array_merge(['debug' => false, 'charset' => 'UTF-8', 'strict_variables' => false, 'autoescape' => 'html', 'cache' => false, 'auto_reload' => null, 'optimizations' => -1, 'use_yield' => false], $options);
        $this->use_yield = (bool) $options['use_yield'];
        $this->debug = (bool) $options['debug'];
        $this->set_charset($options['charset'] ?? 'UTF-8');
        $this->auto_reload = null === $options['auto_reload'] ? $this->debug : (bool) $options['auto_reload'];
        $this->strict_variables = (bool) $options['strict_variables'];
        $this->set_cache($options['cache']);
        $this->extension_set = new Extension_Set();
        $this->default_runtime_loader = new Factory_Runtime_Loader([Escaper_Runtime::class => fn() => new Escaper_Runtime($this->charset)]);
        $this->add_extension(new Core_Extension());
        $escaper_ext = new Escaper_Extension($options['autoescape']);
        $escaper_ext->set_environment($this, false);
        $this->add_extension($escaper_ext);
        $this->add_extension(new Yield_Not_Ready_Extension($this->use_yield));
        $this->add_extension(new Optimizer_Extension($options['optimizations']));
    }
    /**
     * @internal
     */
    public function use_yield(): bool
    {
        return $this->use_yield;
    }
    /**
     * Enables debugging mode.
     */
    public function enable_debug(): void
    {
        $this->debug = true;
        $this->update_options_hash();
    }
    /**
     * Disables debugging mode.
     */
    public function disable_debug(): void
    {
        $this->debug = false;
        $this->update_options_hash();
    }
    /**
     * Checks if debug mode is enabled.
     *
     * @return bool true if debug mode is enabled, false otherwise
     */
    public function is_debug()
    {
        return $this->debug;
    }
    /**
     * Enables the auto_reload option.
     */
    public function enable_auto_reload(): void
    {
        $this->auto_reload = true;
    }
    /**
     * Disables the auto_reload option.
     */
    public function disable_auto_reload(): void
    {
        $this->auto_reload = false;
    }
    /**
     * Checks if the auto_reload option is enabled.
     *
     * @return bool true if auto_reload is enabled, false otherwise
     */
    public function is_auto_reload()
    {
        return $this->auto_reload;
    }
    /**
     * Enables the strict_variables option.
     */
    public function enable_strict_variables(): void
    {
        $this->strict_variables = true;
        $this->update_options_hash();
    }
    /**
     * Disables the strict_variables option.
     */
    public function disable_strict_variables(): void
    {
        $this->strict_variables = false;
        $this->update_options_hash();
    }
    /**
     * Checks if the strict_variables option is enabled.
     *
     * @return bool true if strict_variables is enabled, false otherwise
     */
    public function is_strict_variables()
    {
        return $this->strict_variables;
    }
    public function remove_cache(string $name): void
    {
        $cls = $this->get_template_class($name);
        $this->hot_cache[$name] = $cls . '_' . bin2hex(random_bytes(16));
        if ($this->cache instanceof Removable_Cache_Interface) {
            $this->cache->remove($name, $cls);
        } else {
            throw new \LogicException(\sprintf('The "%s" cache class does not support removing template cache as it does not implement the "RemovableCacheInterface" interface.', $this->cache::class));
        }
    }
    /**
     * Gets the current cache implementation.
     *
     * @param bool $original Whether to return the original cache option or the real cache instance
     *
     * @return CacheInterface|string|false A Twig\Cache\CacheInterface implementation,
     *                                     an absolute path to the compiled templates,
     *                                     or false to disable cache
     */
    public function get_cache($original = true)
    {
        return $original ? $this->original_cache : $this->cache;
    }
    /**
     * Sets the current cache implementation.
     *
     * @param CacheInterface|string|false $cache A Twig\Cache\CacheInterface implementation,
     *                                           an absolute path to the compiled templates,
     *                                           or false to disable cache
     */
    public function set_cache($cache): void
    {
        if (\is_string($cache)) {
            $this->original_cache = $cache;
            $this->cache = new Filesystem_Cache($cache, $this->auto_reload ? Filesystem_Cache::FORCE_BYTECODE_INVALIDATION : 0);
        } elseif (false === $cache) {
            $this->original_cache = $cache;
            $this->cache = new Null_Cache();
        } elseif ($cache instanceof Cache_Interface) {
            $this->original_cache = $this->cache = $cache;
        } else {
            throw new \LogicException('Cache can only be a string, false, or a \Twig\Cache\CacheInterface implementation.');
        }
    }
    /**
     * Gets the template class associated with the given string.
     *
     * The generated template class is based on the following parameters:
     *
     *  * The cache key for the given template;
     *  * The currently enabled extensions;
     *  * PHP version;
     *  * Twig version;
     *  * Options with what environment was created.
     *
     * @param string   $name  The name for which to calculate the template class name
     * @param int|null $index The index if it is an embedded template
     *
     * @internal
     */
    public function get_template_class(string $name, ?int $index = null): string
    {
        $key = ($this->hot_cache[$name] ?? $this->get_loader()->get_cache_key($name)) . $this->options_hash;
        return '__TwigTemplate_' . hash('xxh128', $key) . (null === $index ? '' : '___' . $index);
    }
    /**
     * Renders a template.
     *
     * @param string|TemplateWrapper $name The template name
     *
     * @throws LoaderError  When the template cannot be found
     * @throws SyntaxError  When an error occurred during compilation
     * @throws RuntimeError When an error occurred during rendering
     */
    public function render($name, array $context = []): string
    {
        return $this->load($name)->render($context);
    }
    /**
     * Displays a template.
     *
     * @param string|TemplateWrapper $name The template name
     *
     * @throws LoaderError  When the template cannot be found
     * @throws SyntaxError  When an error occurred during compilation
     * @throws RuntimeError When an error occurred during rendering
     */
    public function display($name, array $context = []): void
    {
        $this->load($name)->display($context);
    }
    /**
     * Loads a template.
     *
     * @param string|TemplateWrapper $name The template name
     *
     * @throws LoaderError  When the template cannot be found
     * @throws RuntimeError When a previously generated cache is corrupted
     * @throws SyntaxError  When an error occurred during compilation
     */
    public function load($name): Template_Wrapper
    {
        if ($name instanceof Template_Wrapper) {
            return $name;
        }
        if ($name instanceof Template) {
            trigger_deprecation('twig/twig', '3.9', 'Passing a "%s" instance to "%s" is deprecated.', self::class, __METHOD__);
            return $name;
        }
        return new Template_Wrapper($this, $this->load_template($this->get_template_class($name), $name));
    }
    /**
     * Loads a template internal representation.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string   $name  The template name
     * @param int|null $index The index if it is an embedded template
     *
     * @throws LoaderError  When the template cannot be found
     * @throws RuntimeError When a previously generated cache is corrupted
     * @throws SyntaxError  When an error occurred during compilation
     *
     * @internal
     */
    public function load_template(string $cls, string $name, ?int $index = null): Template
    {
        $main_cls = $cls;
        if (null !== $index) {
            $cls .= '___' . $index;
        }
        if (isset($this->loaded_templates[$cls])) {
            return $this->loaded_templates[$cls];
        }
        if (!class_exists($cls, false)) {
            $key = $this->cache->generate_key($name, $main_cls);
            if (!$this->is_auto_reload() || $this->is_template_fresh($name, $this->cache->get_timestamp($key))) {
                $this->cache->load($key);
            }
            if (class_exists($cls, false)) {
                $this->extension_set->init_runtime();
                return $this->loaded_templates[$cls] = new $cls($this);
            }
            $source = $this->get_loader()->get_source_context($name);
            $content = $this->compile_source($source);
            if (!isset($this->hot_cache[$name])) {
                $this->cache->write($key, $content);
                $this->cache->load($key);
            }
            if (!class_exists($main_cls, false)) {
                /* Last line of defense if either $this->bcWriteCacheFile was used,
                 * $this->cache is implemented as a no-op or we have a race condition
                 * where the cache was cleared between the above calls to write to and load from
                 * the cache.
                 */
                eval('?>' . $content);
                if (!class_exists($main_cls, false)) {
                    throw new Runtime_Error(\sprintf('Failed to load Twig template "%s", index "%s": cache might be corrupted.', $name, $index), -1, $source);
                }
            }
            if (!class_exists($cls, false)) {
                throw new Runtime_Error(\sprintf('Failed to load Twig template "%s", index "%s": cache might be corrupted.', $name, $index), -1, $source);
            }
        }
        $this->extension_set->init_runtime();
        return $this->loaded_templates[$cls] = new $cls($this);
    }
    /**
     * Creates a template from source.
     *
     * This method should not be used as a generic way to load templates.
     *
     * @param string      $template The template source
     * @param string|null $name     An optional name of the template to be used in error messages
     *
     * @throws LoaderError When the template cannot be found
     * @throws SyntaxError When an error occurred during compilation
     */
    public function create_template(string $template, ?string $name = null): Template_Wrapper
    {
        $hash = hash('xxh128', $template, false);
        if (null !== $name) {
            $name = \sprintf('%s (string template %s)', $name, $hash);
        } else {
            $name = \sprintf('__string_template__%s', $hash);
        }
        $loader = new Chain_Loader([new Array_Loader([$name => $template]), $current = $this->get_loader()]);
        $this->set_loader($loader);
        try {
            return new Template_Wrapper($this, $this->load_template($this->get_template_class($name), $name));
        } finally {
            $this->set_loader($current);
        }
    }
    /**
     * Returns true if the template is still fresh.
     *
     * Besides checking the loader for freshness information,
     * this method also checks if the enabled extensions have
     * not changed.
     *
     * @param int $time The last modification time of the cached template
     */
    public function is_template_fresh(string $name, int $time): bool
    {
        return $this->extension_set->get_last_modified() <= $time && $this->get_loader()->is_fresh($name, $time);
    }
    /**
     * Tries to load a template consecutively from an array.
     *
     * Similar to load() but it also accepts instances of \Twig\TemplateWrapper
     * and an array of templates where each is tried to be loaded.
     *
     * @param string|TemplateWrapper|array<string|TemplateWrapper> $names A template or an array of templates to try consecutively
     *
     * @throws LoaderError When none of the templates can be found
     * @throws SyntaxError When an error occurred during compilation
     */
    public function resolve_template($names): Template_Wrapper
    {
        if (!\is_array($names)) {
            return $this->load($names);
        }
        $count = \count($names);
        foreach ($names as $name) {
            if ($name instanceof Template) {
                trigger_deprecation('twig/twig', '3.9', 'Passing a "%s" instance to "%s" is deprecated.', Template::class, __METHOD__);
                return new Template_Wrapper($this, $name);
            }
            if ($name instanceof Template_Wrapper) {
                return $name;
            }
            if (1 !== $count && !$this->get_loader()->exists($name)) {
                continue;
            }
            return $this->load($name);
        }
        throw new Loader_Error(\sprintf('Unable to find one of the following templates: "%s".', implode('", "', $names)));
    }
    public function set_lexer(Lexer $lexer): void
    {
        $this->lexer = $lexer;
    }
    /**
     * @throws SyntaxError When the code is syntactically wrong
     */
    public function tokenize(Source $source): Token_Stream
    {
        if (null === $this->lexer) {
            $this->lexer = new Lexer($this);
        }
        return $this->lexer->tokenize($source);
    }
    public function set_parser(Parser $parser): void
    {
        $this->parser = $parser;
    }
    /**
     * Converts a token stream to a node tree.
     *
     * @throws SyntaxError When the token stream is syntactically or semantically wrong
     */
    public function parse(Token_Stream $stream): Module_Node
    {
        if (null === $this->parser) {
            $this->parser = new Parser($this);
        }
        return $this->parser->parse($stream);
    }
    public function set_compiler(Compiler $compiler): void
    {
        $this->compiler = $compiler;
    }
    /**
     * Compiles a node and returns the PHP code.
     */
    public function compile(Node $node): string
    {
        if (null === $this->compiler) {
            $this->compiler = new Compiler($this);
        }
        return $this->compiler->compile($node)->get_source();
    }
    /**
     * Compiles a template source code.
     *
     * @throws SyntaxError When there was an error during tokenizing, parsing or compiling
     */
    public function compile_source(Source $source): string
    {
        try {
            return $this->compile($this->parse($this->tokenize($source)));
        } catch (Error $e) {
            $e->set_source_context($source);
            throw $e;
        } catch (\Exception $e) {
            throw new Syntax_Error(\sprintf('An exception has been thrown during the compilation of a template ("%s").', $e->get_message()), -1, $source, $e);
        }
    }
    public function set_loader(Loader_Interface $loader): void
    {
        $this->loader = $loader;
    }
    public function get_loader(): Loader_Interface
    {
        return $this->loader;
    }
    public function set_charset(string $charset): void
    {
        if ('UTF8' === $charset = strtoupper($charset ?: '')) {
            // iconv on Windows requires "UTF-8" instead of "UTF8"
            $charset = 'UTF-8';
        }
        $this->charset = $charset;
    }
    public function get_charset(): string
    {
        return $this->charset;
    }
    public function has_extension(string $class): bool
    {
        return $this->extension_set->has_extension($class);
    }
    public function add_runtime_loader(Runtime_Loader_Interface $loader): void
    {
        $this->runtime_loaders[] = $loader;
    }
    /**
     * @template TExtension of ExtensionInterface
     *
     * @param class-string<TExtension> $class
     *
     * @return TExtension
     */
    public function get_extension(string $class): Extension_Interface
    {
        return $this->extension_set->get_extension($class);
    }
    /**
     * Returns the runtime implementation of a Twig element (filter/function/tag/test).
     *
     * @template TRuntime of object
     *
     * @param class-string<TRuntime> $class A runtime class name
     *
     * @return TRuntime The runtime implementation
     *
     * @throws RuntimeError When the template cannot be found
     */
    public function get_runtime(string $class)
    {
        if (isset($this->runtimes[$class])) {
            return $this->runtimes[$class];
        }
        foreach ($this->runtime_loaders as $loader) {
            if (null !== $runtime = $loader->load($class)) {
                return $this->runtimes[$class] = $runtime;
            }
        }
        if (null !== $runtime = $this->default_runtime_loader->load($class)) {
            return $this->runtimes[$class] = $runtime;
        }
        throw new Runtime_Error(\sprintf('Unable to load the "%s" runtime.', $class));
    }
    public function add_extension(Extension_Interface $extension): void
    {
        $this->extension_set->add_extension($extension);
        $this->update_options_hash();
    }
    /**
     * @param ExtensionInterface[] $extensions An array of extensions
     */
    public function set_extensions(array $extensions): void
    {
        $this->extension_set->set_extensions($extensions);
        $this->update_options_hash();
    }
    /**
     * @return ExtensionInterface[] An array of extensions (keys are for internal usage only and should not be relied on)
     */
    public function get_extensions(): array
    {
        return $this->extension_set->get_extensions();
    }
    public function add_token_parser(Token_Parser_Interface $parser): void
    {
        $this->extension_set->add_token_parser($parser);
    }
    /**
     * @return TokenParserInterface[]
     *
     * @internal
     */
    public function get_token_parsers(): array
    {
        return $this->extension_set->get_token_parsers();
    }
    /**
     * @internal
     */
    public function get_token_parser(string $name): ?Token_Parser_Interface
    {
        return $this->extension_set->get_token_parser($name);
    }
    /**
     * @param callable(string): (TokenParserInterface|false) $callable
     */
    public function register_undefined_token_parser_callback(callable $callable): void
    {
        $this->extension_set->register_undefined_token_parser_callback($callable);
    }
    public function add_node_visitor(Node_Visitor_Interface $visitor): void
    {
        $this->extension_set->add_node_visitor($visitor);
    }
    /**
     * @return NodeVisitorInterface[]
     *
     * @internal
     */
    public function get_node_visitors(): array
    {
        return $this->extension_set->get_node_visitors();
    }
    public function add_filter(Twig_Filter $filter): void
    {
        $this->extension_set->add_filter($filter);
    }
    /**
     * @internal
     */
    public function get_filter(string $name): ?Twig_Filter
    {
        return $this->extension_set->get_filter($name);
    }
    /**
     * @param callable(string): (TwigFilter|false) $callable
     */
    public function register_undefined_filter_callback(callable $callable): void
    {
        $this->extension_set->register_undefined_filter_callback($callable);
    }
    /**
     * Gets the registered Filters.
     *
     * Be warned that this method cannot return filters defined with registerUndefinedFilterCallback.
     *
     * @return TwigFilter[]
     *
     * @see registerUndefinedFilterCallback
     *
     * @internal
     */
    public function get_filters(): array
    {
        return $this->extension_set->get_filters();
    }
    public function add_test(Twig_Test $test): void
    {
        $this->extension_set->add_test($test);
    }
    /**
     * @return TwigTest[]
     *
     * @internal
     */
    public function get_tests(): array
    {
        return $this->extension_set->get_tests();
    }
    /**
     * @internal
     */
    public function get_test(string $name): ?Twig_Test
    {
        return $this->extension_set->get_test($name);
    }
    /**
     * @param callable(string): (TwigTest|false) $callable
     */
    public function register_undefined_test_callback(callable $callable): void
    {
        $this->extension_set->register_undefined_test_callback($callable);
    }
    public function add_function(Twig_Function $function): void
    {
        $this->extension_set->add_function($function);
    }
    /**
     * @internal
     */
    public function get_function(string $name): ?Twig_Function
    {
        return $this->extension_set->get_function($name);
    }
    /**
     * @param callable(string): (TwigFunction|false) $callable
     */
    public function register_undefined_function_callback(callable $callable): void
    {
        $this->extension_set->register_undefined_function_callback($callable);
    }
    /**
     * Gets registered functions.
     *
     * Be warned that this method cannot return functions defined with registerUndefinedFunctionCallback.
     *
     * @return TwigFunction[]
     *
     * @see registerUndefinedFunctionCallback
     *
     * @internal
     */
    public function get_functions(): array
    {
        return $this->extension_set->get_functions();
    }
    /**
     * Registers a Global.
     *
     * New globals can be added before compiling or rendering a template;
     * but after, you can only update existing globals.
     *
     * @param mixed $value The global value
     */
    public function add_global(string $name, $value): void
    {
        if ($this->extension_set->is_initialized() && !\array_key_exists($name, $this->get_globals())) {
            throw new \LogicException(\sprintf('Unable to add global "%s" as the runtime or the extensions have already been initialized.', $name));
        }
        if (null !== $this->resolved_globals) {
            $this->resolved_globals[$name] = $value;
        } else {
            $this->globals[$name] = $value;
        }
    }
    /**
     * @return array<string, mixed>
     */
    public function get_globals(): array
    {
        if ($this->extension_set->is_initialized()) {
            if (null === $this->resolved_globals) {
                $this->resolved_globals = array_merge($this->extension_set->get_globals(), $this->globals);
            }
            return $this->resolved_globals;
        }
        return array_merge($this->extension_set->get_globals(), $this->globals);
    }
    public function reset_globals(): void
    {
        $this->resolved_globals = null;
        $this->extension_set->reset_globals();
    }
    /**
     * @deprecated since Twig 3.14
     */
    public function merge_globals(array $context): array
    {
        trigger_deprecation('twig/twig', '3.14', 'The "%s" method is deprecated.', __METHOD__);
        return $context + $this->get_globals();
    }
    /**
     * @internal
     */
    public function get_expression_parsers(): Expression_Parsers
    {
        return $this->extension_set->get_expression_parsers();
    }
    private function update_options_hash(): void
    {
        $this->options_hash = implode(':', [$this->extension_set->get_signature(), \PHP_MAJOR_VERSION, \PHP_MINOR_VERSION, self::VERSION, (int) $this->debug, (int) $this->strict_variables, $this->use_yield ? '1' : '0']);
    }
}