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

/**
 * Exposes a template to userland.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Template_Wrapper
{
    /**
     * This method is for internal use only and should never be called
     * directly (use Twig\Environment::load() instead).
     *
     * @internal
     */
    public function __construct(private readonly Environment $env, private readonly Template $template)
    {
    }
    /**
     * @return iterable<scalar|\Stringable|null>
     */
    public function stream(array $context = []): iterable
    {
        yield from $this->template->yield($context);
    }
    /**
     * @return iterable<scalar|\Stringable|null>
     */
    public function stream_block(string $name, array $context = []): iterable
    {
        yield from $this->template->yield_block($name, $context);
    }
    public function render(array $context = []): string
    {
        return $this->template->render($context);
    }
    public function display(array $context = []): void
    {
        // using func_get_args() allows to not expose the blocks argument
        // as it should only be used by internal code
        $this->template->display($context, \func_get_args()[1] ?? []);
    }
    public function has_block(string $name, array $context = []): bool
    {
        return $this->template->has_block($name, $context);
    }
    /**
     * @return string[] An array of defined template block names
     */
    public function get_block_names(array $context = []): array
    {
        return $this->template->get_block_names($context);
    }
    public function render_block(string $name, array $context = []): string
    {
        return $this->template->render_block($name, $context + $this->env->get_globals());
    }
    public function display_block(string $name, array $context = []): void
    {
        $context += $this->env->get_globals();
        foreach ($this->template->yield_block($name, $context) as $data) {
            echo $data;
        }
    }
    public function get_source_context(): Source
    {
        return $this->template->get_source_context();
    }
    public function get_template_name(): string
    {
        return $this->template->get_template_name();
    }
    /**
     * @internal
     */
    public function unwrap(): \Twig\Template
    {
        return $this->template;
    }
}