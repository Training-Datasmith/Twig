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
namespace Twig;

use Twig\Error\Syntax_Error;
/**
 * Represents a token stream.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Token_Stream implements \Stringable
{
    private int $current = 0;
    public function __construct(private array $tokens, private ?Source $source = null)
    {
        if (null === $this->source) {
            trigger_deprecation('twig/twig', '3.16', \sprintf('Not passing a "%s" object to "%s" constructor is deprecated.', Source::class, self::class));
            $this->source = new Source('', '');
        }
    }
    public function __toString(): string
    {
        return implode("\n", $this->tokens);
    }
    public function inject_tokens(array $tokens): void
    {
        $this->tokens = array_merge(\array_slice($this->tokens, 0, $this->current), $tokens, \array_slice($this->tokens, $this->current));
    }
    /**
     * Sets the pointer to the next token and returns the old one.
     */
    public function next(): Token
    {
        if (!isset($this->tokens[++$this->current])) {
            throw new Syntax_Error('Unexpected end of template.', $this->tokens[$this->current - 1]->get_line(), $this->source);
        }
        return $this->tokens[$this->current - 1];
    }
    /**
     * Tests a token, sets the pointer to the next one and returns it or throws a syntax error.
     *
     * @return Token|null The next token if the condition is true, null otherwise
     */
    public function next_if($primary, $secondary = null): ?\Twig\Token
    {
        return $this->tokens[$this->current]->test($primary, $secondary) ? $this->next() : null;
    }
    /**
     * Tests a token and returns it or throws a syntax error.
     */
    public function expect($type, $value = null, ?string $message = null): Token
    {
        $token = $this->tokens[$this->current];
        if (!$token->test($type, $value)) {
            $line = $token->get_line();
            throw new Syntax_Error(\sprintf('%sUnexpected token "%s"%s ("%s" expected%s).', $message ? $message . '. ' : '', $token->to_english(), $token->get_value() ? \sprintf(' of value "%s"', $token->get_value()) : '', Token::type_to_english($type), $value ? \sprintf(' with value "%s"', $value) : ''), $line, $this->source);
        }
        $this->next();
        return $token;
    }
    /**
     * Looks at the next token.
     */
    public function look(int $number = 1): Token
    {
        if (!isset($this->tokens[$this->current + $number])) {
            throw new Syntax_Error('Unexpected end of template.', $this->tokens[$this->current + $number - 1]->get_line(), $this->source);
        }
        return $this->tokens[$this->current + $number];
    }
    /**
     * Tests the current token.
     */
    public function test($primary, $secondary = null): bool
    {
        return $this->tokens[$this->current]->test($primary, $secondary);
    }
    /**
     * Checks if end of stream was reached.
     */
    public function is_eof(): bool
    {
        return $this->tokens[$this->current]->test(Token::EOF_TYPE);
    }
    public function get_current(): Token
    {
        return $this->tokens[$this->current];
    }
    public function get_source_context(): Source
    {
        return $this->source;
    }
}