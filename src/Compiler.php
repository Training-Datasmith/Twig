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

use Twig\Node\Node;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Compiler
{
    private ?int $last_line = null;
    private ?string $source = null;
    private ?int $indentation = null;
    private array $debug_info = [];
    private ?int $source_offset = null;
    private ?int $source_line = null;
    private int $var_name_salt = 0;
    private $did_use_echo = false;
    private array $did_use_echo_stack = [];
    public function __construct(private readonly Environment $env)
    {
    }
    public function get_environment(): Environment
    {
        return $this->env;
    }
    public function get_source(): string
    {
        return $this->source;
    }
    /**
     * @return $this
     */
    public function reset(int $indentation = 0): static
    {
        $this->last_line = null;
        $this->source = '';
        $this->debug_info = [];
        $this->source_offset = 0;
        // source code starts at 1 (as we then increment it when we encounter new lines)
        $this->source_line = 1;
        $this->indentation = $indentation;
        $this->var_name_salt = 0;
        return $this;
    }
    /**
     * @return $this
     */
    public function compile(Node $node, int $indentation = 0): static
    {
        $this->reset($indentation);
        $this->did_use_echo_stack[] = $this->did_use_echo;
        try {
            $this->did_use_echo = false;
            $node->compile($this);
            if ($this->did_use_echo) {
                trigger_deprecation('twig/twig', '3.9', 'Using "%s" is deprecated, use "yield" instead in "%s", then flag the class with #[\Twig\Attribute\YieldReady].', $this->did_use_echo, $node::class);
            }
            return $this;
        } finally {
            $this->did_use_echo = array_pop($this->did_use_echo_stack);
        }
    }
    /**
     * @return $this
     */
    public function subcompile(Node $node, bool $raw = true): static
    {
        if (!$raw) {
            $this->source .= str_repeat(' ', $this->indentation * 4);
        }
        $this->did_use_echo_stack[] = $this->did_use_echo;
        try {
            $this->did_use_echo = false;
            $node->compile($this);
            if ($this->did_use_echo) {
                trigger_deprecation('twig/twig', '3.9', 'Using "%s" is deprecated, use "yield" instead in "%s", then flag the class with #[\Twig\Attribute\YieldReady].', $this->did_use_echo, $node::class);
            }
            return $this;
        } finally {
            $this->did_use_echo = array_pop($this->did_use_echo_stack);
        }
    }
    /**
     * Adds a raw string to the compiled code.
     *
     * @return $this
     */
    public function raw(string $string): static
    {
        $this->check_for_echo($string);
        $this->source .= $string;
        return $this;
    }
    /**
     * Writes a string to the compiled code by adding indentation.
     *
     * @return $this
     */
    public function write(...$strings): static
    {
        foreach ($strings as $string) {
            $this->check_for_echo($string);
            $this->source .= str_repeat(' ', $this->indentation * 4) . $string;
        }
        return $this;
    }
    /**
     * Adds a quoted string to the compiled code.
     *
     * @return $this
     */
    public function string(string $value): static
    {
        $this->source .= \sprintf('"%s"', addcslashes($value, "\x00\t\"\$\\"));
        return $this;
    }
    /**
     * Returns a PHP representation of a given value.
     *
     * @return $this
     */
    public function repr($value): static
    {
        if (\is_int($value)) {
            $this->raw((string) $value);
        } elseif (\is_float($value)) {
            if (is_nan($value)) {
                $this->raw('NAN');
            } elseif (\INF === $value) {
                $this->raw('INF');
            } elseif (-\INF === $value) {
                $this->raw('-INF');
            } else {
                $repr = json_encode($value);
                // json_encode omits ".0" for whole floats (e.g. 1.0 → "1")
                if (false !== $repr && !str_contains($repr, '.') && !str_contains($repr, 'e')) {
                    $repr .= '.0';
                }
                $this->raw($repr);
            }
        } elseif (null === $value) {
            $this->raw('null');
        } elseif (\is_bool($value)) {
            $this->raw($value ? 'true' : 'false');
        } elseif (\is_array($value)) {
            $this->raw('[');
            $first = true;
            foreach ($value as $key => $v) {
                if (!$first) {
                    $this->raw(', ');
                }
                $first = false;
                $this->repr($key);
                $this->raw(' => ');
                $this->repr($v);
            }
            $this->raw(']');
        } else {
            $this->string($value);
        }
        return $this;
    }
    /**
     * @return $this
     */
    public function add_debug_info(Node $node): static
    {
        if ($node->get_template_line() != $this->last_line) {
            $this->write(\sprintf("// line %d\n", $node->get_template_line()));
            $this->source_line += substr_count((string) $this->source, "\n", $this->source_offset);
            $this->source_offset = \strlen((string) $this->source);
            $this->debug_info[$this->source_line] = $node->get_template_line();
            $this->last_line = $node->get_template_line();
        }
        return $this;
    }
    public function get_debug_info(): array
    {
        ksort($this->debug_info);
        return $this->debug_info;
    }
    /**
     * @return $this
     */
    public function indent(int $step = 1): static
    {
        $this->indentation += $step;
        return $this;
    }
    /**
     * @return $this
     *
     * @throws \LogicException When trying to outdent too much so the indentation would become negative
     */
    public function outdent(int $step = 1): static
    {
        // can't outdent by more steps than the current indentation level
        if ($this->indentation < $step) {
            throw new \LogicException('Unable to call outdent() as the indentation would become negative.');
        }
        $this->indentation -= $step;
        return $this;
    }
    public function get_var_name(): string
    {
        return \sprintf('_v%d', $this->var_name_salt++);
    }
    private function check_for_echo(string $string): void
    {
        if ($this->did_use_echo) {
            return;
        }
        $this->did_use_echo = preg_match('/^\s*+(echo|print)\b/', $string, $m) ? $m[1] : false;
    }
}