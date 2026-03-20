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
namespace Twig\Expression_Parser\Infix;

use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Unary\Not_Unary;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
final class Is_Not_Expression_Parser extends Is_Expression_Parser
{
    public function parse(Parser $parser, Abstract_Expression $expr, Token $token): \Twig\Node\Expression\Unary\Not_Unary
    {
        return new Not_Unary(parent::parse($parser, $expr, $token), $token->get_line());
    }
    public function get_name(): string
    {
        return 'is not';
    }
}