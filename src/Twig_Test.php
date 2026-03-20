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

use Twig\Node\Expression\Test_Expression;
/**
 * Represents a template test.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @see https://twig.symfony.com/doc/templates.html#test-operator
 */
final class Twig_Test extends Abstract_Twig_Callable
{
    /**
     * @param callable|array{class-string, string}|null $callable A callable implementing the test. If null, you need to overwrite the "node_class" option to customize compilation.
     */
    public function __construct(string $name, $callable = null, array $options = [])
    {
        parent::__construct($name, $callable, $options);
        $this->options = array_merge(['node_class' => Test_Expression::class, 'one_mandatory_argument' => false], $this->options);
    }
    public function get_type(): string
    {
        return 'test';
    }
    public function needs_charset(): bool
    {
        return false;
    }
    public function needs_environment(): bool
    {
        return false;
    }
    public function needs_context(): bool
    {
        return false;
    }
    public function has_one_mandatory_argument(): bool
    {
        return (bool) $this->options['one_mandatory_argument'];
    }
    public function get_minimal_number_of_required_arguments(): int
    {
        return parent::get_minimal_number_of_required_arguments() + 1;
    }
}