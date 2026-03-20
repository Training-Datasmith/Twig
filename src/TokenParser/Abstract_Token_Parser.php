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

use Twig\Lexer;
use Twig\Node\Expression\Variable\Assign_Context_Variable;
use Twig\Node\Nodes;
use Twig\Parser;
use Twig\Token;
/**
 * Base class for all token parsers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Abstract_Token_Parser implements Token_Parser_Interface
{
    /**
     * @var Parser
     */
    protected $parser;
    public function set_parser(Parser $parser): void
    {
        $this->parser = $parser;
    }
    /**
     * Parses an assignment expression like "a, b".
     */
    protected function parse_assignment_expression(): Nodes
    {
        $stream = $this->parser->get_stream();
        $targets = [];
        while (true) {
            $token = $stream->get_current();
            if ($stream->test(Token::OPERATOR_TYPE) && preg_match(Lexer::REGEX_NAME, (string) $token->get_value())) {
                // in this context, string operators are variable names
                $stream->next();
            } else {
                $stream->expect(Token::NAME_TYPE, null, 'Only variables can be assigned to');
            }
            $targets[] = new Assign_Context_Variable($token->get_value(), $token->get_line());
            if (!$stream->next_if(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }
        return new Nodes($targets);
    }
}