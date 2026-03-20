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

use Twig\Node\Expression\Function_Expression;
use Twig\Node\Node;
/**
 * Represents a template function.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @see https://twig.symfony.com/doc/templates.html#functions
 */
final class Twig_Function extends Abstract_Twig_Callable
{
    /**
     * @param callable|array{class-string, string}|null $callable A callable implementing the function. If null, you need to overwrite the "node_class" option to customize compilation.
     */
    public function __construct(string $name, $callable = null, array $options = [])
    {
        parent::__construct($name, $callable, $options);
        $this->options = array_merge(['is_safe' => null, 'is_safe_callback' => null, 'node_class' => Function_Expression::class, 'parser_callable' => null], $this->options);
    }
    public function get_type(): string
    {
        return 'function';
    }
    public function get_parser_callable(): ?callable
    {
        return $this->options['parser_callable'];
    }
    public function get_safe(Node $function_args): ?array
    {
        if (null !== $this->options['is_safe']) {
            return $this->options['is_safe'];
        }
        if (null !== $this->options['is_safe_callback']) {
            return $this->options['is_safe_callback']($function_args);
        }
        return [];
    }
}