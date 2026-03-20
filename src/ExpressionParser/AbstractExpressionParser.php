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
namespace Twig\Expression_Parser;

abstract class Abstract_Expression_Parser implements Expression_Parser_Interface
{
    public function __toString(): string
    {
        return \sprintf('%s(%s)', Expression_Parser_Type::get_type($this)->value, $this->get_name());
    }
    public function get_precedence_change(): ?Precedence_Change
    {
        return null;
    }
    public function get_aliases(): array
    {
        return [];
    }
    public function get_operator_tokens(): array
    {
        return [$this->get_name(), ...$this->get_aliases()];
    }
}