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

/**
 * @template-implements \IteratorAggregate<ExpressionParserInterface>
 *
 * @internal
 */
final class Expression_Parsers implements \IteratorAggregate
{
    /**
     * @var array<class-string<ExpressionParserInterface>, array<string, ExpressionParserInterface>>
     */
    private array $parsers_by_name = [];
    /**
     * @var array<class-string<ExpressionParserInterface>, ExpressionParserInterface>
     */
    private array $parsers_by_class = [];
    /**
     * @var \WeakMap<ExpressionParserInterface, array<ExpressionParserInterface>>|null
     */
    private ?\WeakMap $precedence_changes = null;
    /**
     * @param array<ExpressionParserInterface> $parsers
     */
    public function __construct(array $parsers = [])
    {
        $this->add($parsers);
    }
    /**
     * @param array<ExpressionParserInterface> $parsers
     *
     * @return $this
     */
    public function add(array $parsers): static
    {
        foreach ($parsers as $parser) {
            if ($parser->get_precedence() > 512 || $parser->get_precedence() < 0) {
                trigger_deprecation('twig/twig', '3.21', 'Precedence for "%s" must be between 0 and 512, got %d.', $parser->get_name(), $parser->get_precedence());
                // throw new \InvalidArgumentException(\sprintf('Precedence for "%s" must be between 0 and 512, got %d.', $parser->getName(), $parser->getPrecedence()));
            }
            $interface = $parser instanceof Prefix_Expression_Parser_Interface ? Prefix_Expression_Parser_Interface::class : Infix_Expression_Parser_Interface::class;
            $this->parsers_by_class[$parser::class] = $parser;
            foreach (self::get_operator_tokens_for($parser) as $token) {
                $this->parsers_by_name[$interface][$token] = $parser;
            }
        }
        return $this;
    }
    /**
     * @template T of ExpressionParserInterface
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function get_by_class(string $class): ?Expression_Parser_Interface
    {
        return $this->parsers_by_class[$class] ?? null;
    }
    /**
     * @template T of ExpressionParserInterface
     *
     * @param class-string<T> $interface
     *
     * @return T|null
     */
    public function get_by_name(string $interface, string $name): ?Expression_Parser_Interface
    {
        return $this->parsers_by_name[$interface][$name] ?? null;
    }
    public function getIterator(): \Traversable
    {
        $seen = [];
        foreach ($this->parsers_by_name as $parsers) {
            foreach ($parsers as $parser) {
                $id = spl_object_id($parser);
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    yield $parser;
                }
            }
        }
        foreach ($this->parsers_by_class as $parser) {
            $id = spl_object_id($parser);
            if (!isset($seen[$id])) {
                $seen[$id] = true;
                yield $parser;
            }
        }
    }
    /**
     * @internal
     *
     * @return \WeakMap<ExpressionParserInterface, array<ExpressionParserInterface>>
     */
    public function get_precedence_changes(): \WeakMap
    {
        if (null === $this->precedence_changes) {
            $this->precedence_changes = new \WeakMap();
            foreach ($this as $ep) {
                if (!$ep->get_precedence_change()) {
                    continue;
                }
                $min = min($ep->get_precedence_change()->get_new_precedence(), $ep->get_precedence());
                $max = max($ep->get_precedence_change()->get_new_precedence(), $ep->get_precedence());
                foreach ($this as $e) {
                    if ($e->get_precedence() > $min && $e->get_precedence() < $max) {
                        if (!isset($this->precedence_changes[$e])) {
                            $this->precedence_changes[$e] = [];
                        }
                        $this->precedence_changes[$e][] = $ep;
                    }
                }
            }
        }
        return $this->precedence_changes;
    }
    /**
     * @internal
     *
     * @return array<string>
     */
    public static function get_operator_tokens_for(Expression_Parser_Interface $parser): array
    {
        if (method_exists($parser, 'getOperatorTokens')) {
            return $parser->get_operator_tokens();
        }
        trigger_deprecation('twig/twig', '3.24', 'Not implementing the "getOperatorTokens()" method in "%s" is deprecated. This method will be part of the "%s" interface in 4.0.', $parser::class, Expression_Parser_Interface::class);
        return [$parser->get_name(), ...$parser->get_aliases()];
    }
}