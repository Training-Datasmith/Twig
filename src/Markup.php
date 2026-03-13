<?php

declare(strict_types=1);

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
 * Marks a content as safe.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Markup implements \Countable, \JsonSerializable, \Stringable
{
    private readonly string $content;

    public function __construct($content, private readonly ?string $charset)
    {
        $this->content = (string) $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function count(): int
    {
        return mb_strlen($this->content, $this->charset);
    }

    public function jsonSerialize(): mixed
    {
        return $this->content;
    }
}
