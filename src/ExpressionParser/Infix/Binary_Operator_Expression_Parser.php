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

use Twig\Expression_Parser\Abstract_Expression_Parser;
use Twig\Expression_Parser\Expression_Parser_Description_Interface;
use Twig\Expression_Parser\Infix_Associativity;
use Twig\Expression_Parser\Infix_Expression_Parser_Interface;
use Twig\Expression_Parser\Precedence_Change;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Binary\Abstract_Binary;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
class Binary_Operator_Expression_Parser extends Abstract_Expression_Parser implements Infix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    public function __construct(
        /** @var class-string<AbstractBinary> */
        private readonly string $node_class,
        private readonly string $name,
        private readonly int $precedence,
        private readonly Infix_Associativity $associativity = Infix_Associativity::Left,
        private readonly ?Precedence_Change $precedence_change = null,
        private readonly ?string $description = null,
        private readonly array $aliases = []
    )
    {
    }
    /**
     * @return AbstractBinary
     */
    public function parse(Parser $parser, Abstract_Expression $left, Token $token): Abstract_Expression
    {
        $right = $parser->parse_expression(Infix_Associativity::Left === $this->get_associativity() ? $this->get_precedence() + 1 : $this->get_precedence());
        return new $this->node_class($left, $right, $token->get_line());
    }
    public function get_associativity(): Infix_Associativity
    {
        return $this->associativity;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_description(): string
    {
        return $this->description ?? '';
    }
    public function get_precedence(): int
    {
        return $this->precedence;
    }
    public function get_precedence_change(): ?Precedence_Change
    {
        return $this->precedence_change;
    }
    public function get_aliases(): array
    {
        return $this->aliases;
    }
}