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

use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Parser;
use Twig\Token;
interface Prefix_Expression_Parser_Interface extends Expression_Parser_Interface
{
    /**
     * @throws SyntaxError
     */
    public function parse(Parser $parser, Token $token): Abstract_Expression;
}