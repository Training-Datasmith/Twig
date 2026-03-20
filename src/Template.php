<?php

declare (strict_types=1);
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Twig;

use Twig\Error\Error;
use Twig\Error\Runtime_Error;
/**
 * Default base class for compiled templates.
 *
 * This class is an implementation detail of how template compilation currently
 * works, which might change. It should never be used directly. Use $twig->load()
 * instead, which returns an instance of \Twig\TemplateWrapper.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
abstract class Template
{
    public const ANY_CALL = 'any';
    public const ARRAY_CALL = 'array';
    public const METHOD_CALL = 'method';
    protected $parent;
    protected $parents = [];
    protected $blocks = [];
    protected $traits = [];
    protected $trait_aliases = [];
    protected array $extensions;
    protected $sandbox;
    private readonly bool $use_yield;
    public function __construct(protected Environment $env)
    {
        $this->use_yield = $env->use_yield();
        $this->extensions = $env->get_extensions();
    }
    /**
     * Returns the template name.
     */
    abstract public function get_template_name(): string;
    /**
     * Returns debug information about the template.
     *
     * @return array<int, int> Debug information
     */
    abstract public function get_debug_info(): array;
    /**
     * Returns information about the original template source code.
     */
    abstract public function get_source_context(): Source;
    /**
     * Returns the parent template.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @return self|TemplateWrapper|false The parent template or false if there is no parent
     */
    public function get_parent(array $context): self|Template_Wrapper|false
    {
        if (null !== $this->parent) {
            return $this->parent;
        }
        if (!$parent = $this->do_get_parent($context)) {
            return false;
        }
        if ($parent instanceof self || $parent instanceof Template_Wrapper) {
            return $this->parents[$parent->get_source_context()->get_name()] = $parent;
        }
        if (!isset($this->parents[$parent])) {
            $this->parents[$parent] = $this->load($parent, -1);
        }
        return $this->parents[$parent];
    }
    protected function do_get_parent(array $context): bool|string|self|Template_Wrapper
    {
        return false;
    }
    public function is_traitable(): bool
    {
        return true;
    }
    /**
     * Displays a parent block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name    The block name to display from the parent
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     */
    public function display_parent_block($name, array $context, array $blocks = []): void
    {
        foreach ($this->yield_parent_block($name, $context, $blocks) as $data) {
            echo $data;
        }
    }
    /**
     * Displays a block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name      The block name to display
     * @param array  $context   The context
     * @param array  $blocks    The current set of blocks
     * @param bool   $useBlocks Whether to use the current set of blocks
     */
    public function display_block($name, array $context, array $blocks = [], $use_blocks = true, ?self $template_context = null): void
    {
        foreach ($this->yield_block($name, $context, $blocks, $use_blocks, $template_context) as $data) {
            echo $data;
        }
    }
    /**
     * Renders a parent block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name    The block name to render from the parent
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     *
     * @return string The rendered block
     */
    public function render_parent_block($name, array $context, array $blocks = []): string
    {
        if (!$this->use_yield) {
            if ($this->env->is_debug()) {
                ob_start();
            } else {
                ob_start(static fn() => '');
            }
            $this->display_parent_block($name, $context, $blocks);
            return ob_get_clean();
        }
        $content = '';
        foreach ($this->yield_parent_block($name, $context, $blocks) as $data) {
            $content .= $data;
        }
        return $content;
    }
    /**
     * Renders a block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name      The block name to render
     * @param array  $context   The context
     * @param array  $blocks    The current set of blocks
     * @param bool   $useBlocks Whether to use the current set of blocks
     *
     * @return string The rendered block
     */
    public function render_block($name, array $context, array $blocks = [], $use_blocks = true): string
    {
        if (!$this->use_yield) {
            $level = ob_get_level();
            if ($this->env->is_debug()) {
                ob_start();
            } else {
                ob_start(static fn() => '');
            }
            try {
                $this->display_block($name, $context, $blocks, $use_blocks);
            } catch (\Throwable $e) {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
                throw $e;
            }
            return ob_get_clean();
        }
        $content = '';
        foreach ($this->yield_block($name, $context, $blocks, $use_blocks) as $data) {
            $content .= $data;
        }
        return $content;
    }
    /**
     * Returns whether a block exists or not in the current context of the template.
     *
     * This method checks blocks defined in the current template
     * or defined in "used" traits or defined in parent templates.
     *
     * @param string $name    The block name
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     *
     * @return bool true if the block exists, false otherwise
     */
    public function has_block($name, array $context, array $blocks = []): bool
    {
        if (isset($blocks[$name])) {
            return $blocks[$name][0] instanceof self;
        }
        if (isset($this->blocks[$name])) {
            return true;
        }
        if ($parent = $this->get_parent($context)) {
            return $parent->has_block($name, $context);
        }
        return false;
    }
    /**
     * Returns all block names in the current context of the template.
     *
     * This method checks blocks defined in the current template
     * or defined in "used" traits or defined in parent templates.
     *
     * @param array $context The context
     * @param array $blocks  The current set of blocks
     *
     * @return array<string> An array of block names
     */
    public function get_block_names(array $context, array $blocks = []): array
    {
        $names = array_merge(array_keys($blocks), array_keys($this->blocks));
        if ($parent = $this->get_parent($context)) {
            $names = array_merge($names, $parent->get_block_names($context));
        }
        return array_unique($names);
    }
    /**
     * @param string|TemplateWrapper|array<string|TemplateWrapper> $template
     */
    protected function load(string|Template_Wrapper|array $template, int $line, ?int $index = null): self
    {
        try {
            if (\is_array($template)) {
                return $this->env->resolve_template($template)->unwrap();
            }
            if ($template instanceof Template_Wrapper) {
                return $template->unwrap();
            }
            if ($template === $this->get_template_name()) {
                $class = static::class;
                if (false !== $pos = strrpos($class, '___', -1)) {
                    $class = substr($class, 0, $pos);
                }
            } else {
                $class = $this->env->get_template_class($template);
            }
            return $this->env->load_template($class, $template, $index);
        } catch (Error $e) {
            if (!$e->get_source_context()) {
                $e->set_source_context($this->get_source_context());
            }
            if ($e->get_template_line() > 0) {
                throw $e;
            }
            if (-1 === $line) {
                $e->guess();
            } else {
                $e->set_template_line($line);
            }
            throw $e;
        }
    }
    /**
     * @param string|TemplateWrapper|array<string|TemplateWrapper> $template
     *
     * @deprecated since Twig 3.21 and will be removed in 4.0. Use Template::load() instead.
     */
    protected function load_template($template, $template_name = null, ?int $line = null, ?int $index = null): self|Template_Wrapper
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s" method is deprecated.', __METHOD__);
        if (null === $line) {
            $line = -1;
        }
        if ($template instanceof self) {
            return $template;
        }
        return $this->load($template, $line, $index);
    }
    /**
     * @internal
     *
     * @return $this
     */
    public function unwrap(): self
    {
        return $this;
    }
    /**
     * Returns all blocks.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @return array An array of blocks
     */
    public function get_blocks(): array
    {
        return $this->blocks;
    }
    public function display(array $context, array $blocks = []): void
    {
        foreach ($this->yield($context, $blocks) as $data) {
            echo $data;
        }
    }
    public function render(array $context): string
    {
        if (!$this->use_yield) {
            $level = ob_get_level();
            if ($this->env->is_debug()) {
                ob_start();
            } else {
                ob_start(static fn() => '');
            }
            try {
                $this->display($context);
            } catch (\Throwable $e) {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
                throw $e;
            }
            return ob_get_clean();
        }
        $content = '';
        foreach ($this->yield($context) as $data) {
            $content .= $data;
        }
        return $content;
    }
    /**
     * @return iterable<scalar|\Stringable|null>
     */
    public function yield(array $context, array $blocks = []): iterable
    {
        $context += $this->env->get_globals();
        $blocks = array_merge($this->blocks, $blocks);
        try {
            yield from $this->do_display($context, $blocks);
        } catch (Error $e) {
            if (!$e->get_source_context()) {
                $e->set_source_context($this->get_source_context());
            }
            // this is mostly useful for \Twig\Error\LoaderError exceptions
            // see \Twig\Error\LoaderError
            if (-1 === $e->get_template_line()) {
                $e->guess();
            }
            throw $e;
        } catch (\Throwable $e) {
            $e = new Runtime_Error(\sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->get_message()), -1, $this->get_source_context(), $e);
            $e->guess();
            throw $e;
        }
    }
    /**
     * @return iterable<scalar|\Stringable|null>
     */
    public function yield_block($name, array $context, array $blocks = [], $use_blocks = true, ?self $template_context = null): iterable
    {
        if ($use_blocks && isset($blocks[$name])) {
            $template = $blocks[$name][0];
            $block = $blocks[$name][1];
        } elseif (isset($this->blocks[$name])) {
            $template = $this->blocks[$name][0];
            $block = $this->blocks[$name][1];
        } else {
            $template = null;
            $block = null;
        }
        // avoid RCEs when sandbox is enabled
        if (null !== $template && !$template instanceof self) {
            throw new \LogicException('A block must be a method on a \Twig\Template instance.');
        }
        if (null !== $template) {
            try {
                yield from $template->{$block}($context, $blocks);
            } catch (Error $e) {
                if (!$e->get_source_context()) {
                    $e->set_source_context($template->get_source_context());
                }
                // this is mostly useful for \Twig\Error\LoaderError exceptions
                // see \Twig\Error\LoaderError
                if (-1 === $e->get_template_line()) {
                    $e->guess();
                }
                throw $e;
            } catch (\Throwable $e) {
                $e = new Runtime_Error(\sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->get_message()), -1, $template->get_source_context(), $e);
                $e->guess();
                throw $e;
            }
        } elseif ($parent = $this->get_parent($context)) {
            yield from $parent->unwrap()->yield_block($name, $context, array_merge($this->blocks, $blocks), false, $template_context ?? $this);
        } elseif (isset($blocks[$name])) {
            throw new Runtime_Error(\sprintf('Block "%s" should not call parent() in "%s" as the block does not exist in the parent template "%s".', $name, $blocks[$name][0]->get_template_name(), $this->get_template_name()), -1, $blocks[$name][0]->get_source_context());
        } else {
            throw new Runtime_Error(\sprintf('Block "%s" on template "%s" does not exist.', $name, $this->get_template_name()), -1, ($template_context ?? $this)->get_source_context());
        }
    }
    /**
     * Yields a parent block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name    The block name to display from the parent
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     *
     * @return iterable<scalar|\Stringable|null>
     */
    public function yield_parent_block($name, array $context, array $blocks = []): iterable
    {
        if (isset($this->traits[$name])) {
            yield from $this->traits[$name][0]->yield_block($this->trait_aliases[$name] ?? $name, $context, $blocks, false);
        } elseif ($parent = $this->get_parent($context)) {
            yield from $parent->unwrap()->yield_block($name, $context, $blocks, false);
        } else {
            throw new Runtime_Error(\sprintf('The template has no parent and no traits defining the "%s" block.', $name), -1, $this->get_source_context());
        }
    }
    protected function has_macro(string $name, array $context): bool
    {
        if (method_exists($this, $name)) {
            return true;
        }
        if (!$parent = $this->get_parent($context)) {
            return false;
        }
        return $parent->has_macro($name, $context);
    }
    protected function get_template_for_macro(string $name, array $context, int $line, Source $source): self
    {
        if (method_exists($this, $name)) {
            return $this;
        }
        $parent = $this;
        while ($parent = $parent->get_parent($context)) {
            if (method_exists($parent, $name)) {
                return $parent;
            }
        }
        throw new Runtime_Error(\sprintf('Macro "%s" is not defined in template "%s".', substr($name, \strlen('macro_')), $this->get_template_name()), $line, $source);
    }
    /**
     * Auto-generated method to display the template with the given context.
     *
     * @param array $context An array of parameters to pass to the template
     * @param array $blocks  An array of blocks to pass to the template
     *
     * @return iterable<scalar|\Stringable|null>
     */
    abstract protected function do_display(array $context, array $blocks = []): iterable;
}