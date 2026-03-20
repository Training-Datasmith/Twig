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

/**
 * @internal
 */
enum Expression_Parser_Type : string
{
    case Prefix = 'prefix';
    case Infix = 'infix';
    public static function get_type(object $object): Expression_Parser_Type
    {
        if ($object instanceof Prefix_Expression_Parser_Interface) {
            return self::Prefix;
        }
        if ($object instanceof Infix_Expression_Parser_Interface) {
            return self::Infix;
        }
        throw new \InvalidArgumentException(\sprintf('Unsupported expression parser type: %s', $object::class));
    }
}