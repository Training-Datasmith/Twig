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

use Twig\Compiler;
use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Variable\Assign_Context_Variable;
use Twig\Node\Expression\Variable\Context_Variable;
class Assign_Name_Expression extends Context_Variable
{
    public function __construct(string $name, int $lineno)
    {
        if (self::class === static::class) {
            trigger_deprecation('twig/twig', '3.15', 'The "%s" class is deprecated, use "%s" instead.', self::class, Assign_Context_Variable::class);
        }
        // All names supported by ExpressionParser::parsePrimaryExpression() should be excluded
        if (\in_array(strtolower($name), ['true', 'false', 'none', 'null'], true)) {
            throw new Syntax_Error(\sprintf('You cannot assign a value to "%s".', $name), $lineno);
        }
        parent::__construct($name, $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('$context[')->string($this->get_attribute('name'))->raw(']');
    }
}