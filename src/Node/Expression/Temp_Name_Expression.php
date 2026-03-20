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
namespace Twig\Node\Expression;

use Twig\Compiler;
use Twig\Error\Syntax_Error;
class Temp_Name_Expression extends Abstract_Expression
{
    public const RESERVED_NAMES = ['varargs', 'context', 'macros', 'blocks', 'this'];
    public function __construct(string|int|null $name, int $lineno)
    {
        // All names supported by ExpressionParser::parsePrimaryExpression() should be excluded
        if ($name && \in_array(strtolower((string) $name), ['true', 'false', 'none', 'null'], true)) {
            throw new Syntax_Error(\sprintf('You cannot assign a value to "%s".', $name), $lineno);
        }
        if (self::class === static::class) {
            trigger_deprecation('twig/twig', '3.15', 'The "%s" class is deprecated.', self::class);
        }
        if (null !== $name && (\is_int($name) || ctype_digit($name))) {
            $name = (int) $name;
        } elseif (\in_array($name, self::RESERVED_NAMES, true)) {
            $name = "͜" . $name;
        }
        parent::__construct([], ['name' => $name], $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        if (null === $this->get_attribute('name')) {
            $this->set_attribute('name', $compiler->get_var_name());
        }
        $compiler->raw('$' . $this->get_attribute('name'));
    }
}