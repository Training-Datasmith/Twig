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
namespace Twig\Token_Parser;

use Twig\Error\Syntax_Error;
use Twig\Node\Node;
use Twig\Parser;
use Twig\Token;
/**
 * Interface implemented by token parsers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface Token_Parser_Interface
{
    /**
     * Sets the parser associated with this token parser.
     */
    public function set_parser(Parser $parser): void;
    /**
     * Parses a token and returns a node.
     *
     * @return Node
     *
     * @throws SyntaxError
     */
    public function parse(Token $token);
    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string
     */
    public function get_tag();
}