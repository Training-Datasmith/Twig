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

use Twig\Error\Syntax_Error;
use Twig\Expression_Parser\Infix_Associativity;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Array_Expression;
use Twig\Node\Expression\Binary\Abstract_Binary;
use Twig\Node\Expression\Binary\Object_Destructuring_Set_Binary;
use Twig\Node\Expression\Binary\Sequence_Destructuring_Set_Binary;
use Twig\Node\Expression\Binary\Set_Binary;
use Twig\Node\Expression\Variable\Context_Variable;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
class Assignment_Expression_Parser extends Binary_Operator_Expression_Parser
{
    public function __construct(string $name)
    {
        parent::__construct(Set_Binary::class, $name, 0, Infix_Associativity::Right);
    }
    /**
     * @return AbstractBinary
     */
    public function parse(Parser $parser, Abstract_Expression $left, Token $token): Abstract_Expression
    {
        if (!$left instanceof Context_Variable && !$left instanceof Array_Expression) {
            throw new Syntax_Error(\sprintf('Cannot assign to "%s", only variables can be assigned.', $left::class), $token->get_line(), $parser->get_stream()->get_source_context());
        }
        $right = $parser->parse_expression(Infix_Associativity::Left === $this->get_associativity() ? $this->get_precedence() + 1 : $this->get_precedence());
        $right = match ($this->get_name()) {
            '=' => $right,
            default => throw new \LogicException(\sprintf('Unknown operator: %s.', $this->get_name())),
        };
        if ($left instanceof Array_Expression) {
            if ($left->is_sequence()) {
                return new Sequence_Destructuring_Set_Binary($left, $right, $token->get_line());
            }
            return new Object_Destructuring_Set_Binary($left, $right, $token->get_line());
        }
        return new Set_Binary($left, $right, $token->get_line());
    }
    public function get_description(): string
    {
        return 'Assignment operator';
    }
}