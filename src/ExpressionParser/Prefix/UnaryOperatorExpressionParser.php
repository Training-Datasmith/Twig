<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\ExpressionParser\Prefix;

use Twig\ExpressionParser\AbstractExpressionParser;
use Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Twig\ExpressionParser\PrecedenceChange;
use Twig\ExpressionParser\PrefixExpressionParserInterface;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\Unary\AbstractUnary;
use Twig\Parser;
use Twig\Token;

/**
 * @internal
 */
final class UnaryOperatorExpressionParser extends AbstractExpressionParser implements PrefixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    public function __construct(
        /** @var class-string<AbstractUnary> */
        private readonly string $nodeClass,
        private readonly string $name,
        private readonly int $precedence,
        private readonly ?PrecedenceChange $precedenceChange = null,
        private readonly ?string $description = null,
        private readonly array $aliases = [],
        private readonly ?int $operandPrecedence = null,
    ) {
    }

    /**
     * @return AbstractUnary
     */
    public function parse(Parser $parser, Token $token): AbstractExpression
    {
        return new ($this->nodeClass)($parser->parseExpression($this->operandPrecedence ?? $this->precedence), $token->getLine());
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    public function getPrecedenceChange(): ?PrecedenceChange
    {
        return $this->precedenceChange;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }
}
