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

use Twig\Node\Expression\Filter_Expression;
use Twig\Node\Node;
/**
 * Represents a template filter.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @see https://twig.symfony.com/doc/templates.html#filters
 */
final class Twig_Filter extends Abstract_Twig_Callable
{
    /**
     * @param callable|array{class-string, string}|null $callable A callable implementing the filter. If null, you need to overwrite the "node_class" option to customize compilation.
     */
    public function __construct(string $name, $callable = null, array $options = [])
    {
        parent::__construct($name, $callable, $options);
        $this->options = array_merge(['is_safe' => null, 'is_safe_callback' => null, 'pre_escape' => null, 'preserves_safety' => null, 'node_class' => Filter_Expression::class], $this->options);
    }
    public function get_type(): string
    {
        return 'filter';
    }
    public function get_safe(Node $filter_args): ?array
    {
        if (null !== $this->options['is_safe']) {
            return $this->options['is_safe'];
        }
        if (null !== $this->options['is_safe_callback']) {
            return $this->options['is_safe_callback']($filter_args);
        }
        return [];
    }
    public function get_preserves_safety(): array
    {
        return $this->options['preserves_safety'] ?? [];
    }
    public function get_pre_escape(): ?string
    {
        return $this->options['pre_escape'];
    }
    public function get_minimal_number_of_required_arguments(): int
    {
        return parent::get_minimal_number_of_required_arguments() + 1;
    }
}