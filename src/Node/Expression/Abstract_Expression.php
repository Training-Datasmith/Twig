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
namespace Twig\Node\Expression;

use Twig\Node\Node;
/**
 * Abstract class for all nodes that represents an expression.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Abstract_Expression extends Node
{
    public function is_generator(): bool
    {
        return $this->has_attribute('is_generator') && $this->get_attribute('is_generator');
    }
    /**
     * @return static
     */
    public function set_explicit_parentheses(): self
    {
        $this->set_attribute('with_parentheses', true);
        return $this;
    }
    public function has_explicit_parentheses(): bool
    {
        return $this->has_attribute('with_parentheses') && $this->get_attribute('with_parentheses');
    }
}