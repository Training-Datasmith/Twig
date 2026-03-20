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
namespace Twig\Expression_Parser\Prefix;

use Twig\Expression_Parser\Abstract_Expression_Parser;
use Twig\Expression_Parser\Expression_Parser_Description_Interface;
use Twig\Expression_Parser\Precedence_Change;
use Twig\Expression_Parser\Prefix_Expression_Parser_Interface;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Unary\Abstract_Unary;
use Twig\Parser;
use Twig\Token;
/**
 * @internal
 */
final class Unary_Operator_Expression_Parser extends Abstract_Expression_Parser implements Prefix_Expression_Parser_Interface, Expression_Parser_Description_Interface
{
    public function __construct(
        /** @var class-string<AbstractUnary> */
        private readonly string $node_class,
        private readonly string $name,
        private readonly int $precedence,
        private readonly ?Precedence_Change $precedence_change = null,
        private readonly ?string $description = null,
        private readonly array $aliases = [],
        private readonly ?int $operand_precedence = null
    )
    {
    }
    /**
     * @return AbstractUnary
     */
    public function parse(Parser $parser, Token $token): Abstract_Expression
    {
        return new $this->node_class($parser->parse_expression($this->operand_precedence ?? $this->precedence), $token->get_line());
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