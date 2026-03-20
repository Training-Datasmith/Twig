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
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface Twig_Callable_Interface extends \Stringable
{
    public function get_name(): string;
    public function get_type(): string;
    public function get_dynamic_name(): string;
    /**
     * @return callable|array{class-string, string}|null
     */
    public function get_callable();
    public function get_node_class(): string;
    public function needs_charset(): bool;
    public function needs_environment(): bool;
    public function needs_context(): bool;
    public function with_dynamic_arguments(string $name, string $dynamic_name, array $arguments): self;
    public function get_arguments(): array;
    public function is_variadic(): bool;
    public function is_deprecated(): bool;
    public function get_deprecating_package(): string;
    public function get_deprecated_version(): string;
    public function get_alternative(): ?string;
    public function get_minimal_number_of_required_arguments(): int;
}