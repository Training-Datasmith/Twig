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
namespace Twig\Node;

use Twig\Attribute\Yield_Ready;
use Twig\Compiler;
use Twig\Source;
/**
 * Represents a node in the AST.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @implements \IteratorAggregate<int|string, Node>
 */
#[Yield_Ready]
class Node implements \Countable, \IteratorAggregate, \Stringable
{
    /**
     * @var array<string|int, Node>
     */
    protected $nodes;
    protected $tag;
    private ?\Twig\Source $source_context = null;
    /** @var array<string, NameDeprecation> */
    private array $node_name_deprecations = [];
    /** @var array<string, NameDeprecation> */
    private array $attribute_name_deprecations = [];
    /**
     * @param array<string|int, Node> $nodes      An array of named nodes
     * @param array                   $attributes An array of attributes (should not be nodes)
     * @param int                     $lineno     The line number
     */
    public function __construct(array $nodes = [], protected array $attributes = [], protected int $lineno = 0)
    {
        if (self::class === static::class) {
            trigger_deprecation('twig/twig', '3.15', \sprintf('Instantiating "%s" directly is deprecated; the class will become abstract in 4.0.', self::class));
        }
        foreach ($nodes as $name => $node) {
            if (!$node instanceof self) {
                throw new \InvalidArgumentException(\sprintf('Using "%s" for the value of node "%s" of "%s" is not supported. You must pass a \Twig\Node\Node instance.', get_debug_type($node), $name, static::class));
            }
        }
        $this->nodes = $nodes;
        if (\func_num_args() > 3) {
            trigger_deprecation('twig/twig', '3.12', \sprintf('The "tag" constructor argument of the "%s" class is deprecated and ignored (check which TokenParser class set it to "%s"), the tag is now automatically set by the Parser when needed.', static::class, func_get_arg(3) ?: 'null'));
        }
    }
    public function __toString(): string
    {
        $repr = static::class;
        if ($this->tag) {
            $repr .= \sprintf("\n  tag: %s", $this->tag);
        }
        $attributes = [];
        foreach ($this->attributes as $name => $value) {
            if (\is_callable($value)) {
                $v = '\Closure';
            } elseif ($value instanceof \Stringable) {
                $v = (string) $value;
            } else {
                $v = str_replace("\n", '', var_export($value, true));
            }
            $attributes[] = \sprintf('%s: %s', $name, $v);
        }
        if ($attributes) {
            $repr .= \sprintf("\n  attributes:\n    %s", implode("\n    ", $attributes));
        }
        if (\count($this->nodes)) {
            $repr .= "\n  nodes:";
            foreach ($this->nodes as $name => $node) {
                $len = \strlen((string) $name) + 6;
                $noderepr = [];
                foreach (explode("\n", (string) $node) as $line) {
                    $noderepr[] = str_repeat(' ', $len) . $line;
                }
                $repr .= \sprintf("\n    %s: %s", $name, ltrim(implode("\n", $noderepr)));
            }
        }
        return $repr;
    }
    public function __clone()
    {
        foreach ($this->nodes as $name => $node) {
            $this->nodes[$name] = clone $node;
        }
    }
    public function compile(Compiler $compiler): void
    {
        foreach ($this->nodes as $node) {
            $compiler->subcompile($node);
        }
    }
    public function get_template_line(): int
    {
        return $this->lineno;
    }
    public function get_node_tag(): ?string
    {
        return $this->tag;
    }
    /**
     * @internal
     */
    public function set_node_tag(string $tag): void
    {
        if ($this->tag) {
            throw new \LogicException('The tag of a node can only be set once.');
        }
        $this->tag = $tag;
    }
    public function has_attribute(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }
    public function get_attribute(string $name)
    {
        if (!\array_key_exists($name, $this->attributes)) {
            throw new \LogicException(\sprintf('Attribute "%s" does not exist for Node "%s".', $name, static::class));
        }
        $trigger_deprecation = \func_num_args() > 1 ? func_get_arg(1) : true;
        if ($trigger_deprecation && isset($this->attribute_name_deprecations[$name])) {
            $dep = $this->attribute_name_deprecations[$name];
            if ($dep->get_new_name()) {
                trigger_deprecation($dep->get_package(), $dep->get_version(), 'Getting attribute "%s" on a "%s" class is deprecated, get the "%s" attribute instead.', $name, static::class, $dep->get_new_name());
            } else {
                trigger_deprecation($dep->get_package(), $dep->get_version(), 'Getting attribute "%s" on a "%s" class is deprecated.', $name, static::class);
            }
        }
        return $this->attributes[$name];
    }
    public function set_attribute(string $name, $value): void
    {
        $trigger_deprecation = \func_num_args() > 2 ? func_get_arg(2) : true;
        if ($trigger_deprecation && isset($this->attribute_name_deprecations[$name])) {
            $dep = $this->attribute_name_deprecations[$name];
            if ($dep->get_new_name()) {
                trigger_deprecation($dep->get_package(), $dep->get_version(), 'Setting attribute "%s" on a "%s" class is deprecated, set the "%s" attribute instead.', $name, static::class, $dep->get_new_name());
            } else {
                trigger_deprecation($dep->get_package(), $dep->get_version(), 'Setting attribute "%s" on a "%s" class is deprecated.', $name, static::class);
            }
        }
        $this->attributes[$name] = $value;
    }
    public function deprecate_attribute(string $name, Name_Deprecation $dep): void
    {
        $this->attribute_name_deprecations[$name] = $dep;
    }
    public function remove_attribute(string $name): void
    {
        unset($this->attributes[$name]);
    }
    public function has_node(string|int $name): bool
    {
        return isset($this->nodes[$name]);
    }
    public function get_node(string|int $name): self
    {
        if (!isset($this->nodes[$name])) {
            throw new \LogicException(\sprintf('Node "%s" does not exist for Node "%s".', $name, static::class));
        }
        $trigger_deprecation = \func_num_args() > 1 ? func_get_arg(1) : true;
        if ($trigger_deprecation && isset($this->node_name_deprecations[$name])) {
            $dep = $this->node_name_deprecations[$name];
            if ($dep->get_new_name()) {
                trigger_deprecation($dep->get_package(), $dep->get_version(), 'Getting node "%s" on a "%s" class is deprecated, get the "%s" node instead.', $name, static::class, $dep->get_new_name());
            } else {
                trigger_deprecation($dep->get_package(), $dep->get_version(), 'Getting node "%s" on a "%s" class is deprecated.', $name, static::class);
            }
        }
        return $this->nodes[$name];
    }
    public function set_node(string|int $name, self $node): void
    {
        $trigger_deprecation = \func_num_args() > 2 ? func_get_arg(2) : true;
        if ($trigger_deprecation && isset($this->node_name_deprecations[$name])) {
            $dep = $this->node_name_deprecations[$name];
            if ($dep->get_new_name()) {
                trigger_deprecation($dep->get_package(), $dep->get_version(), 'Setting node "%s" on a "%s" class is deprecated, set the "%s" node instead.', $name, static::class, $dep->get_new_name());
            } else {
                trigger_deprecation($dep->get_package(), $dep->get_version(), 'Setting node "%s" on a "%s" class is deprecated.', $name, static::class);
            }
        }
        if (null !== $this->source_context) {
            $node->set_source_context($this->source_context);
        }
        $this->nodes[$name] = $node;
    }
    public function remove_node(string|int $name): void
    {
        unset($this->nodes[$name]);
    }
    public function deprecate_node(string $name, Name_Deprecation $dep): void
    {
        $this->node_name_deprecations[$name] = $dep;
    }
    /**
     * @return int
     */
    #[\Return_Type_Will_Change]
    public function count()
    {
        return \count($this->nodes);
    }
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->nodes);
    }
    public function get_template_name(): ?string
    {
        return $this->source_context ? $this->source_context->get_name() : null;
    }
    public function set_source_context(Source $source): void
    {
        $this->source_context = $source;
        foreach ($this->nodes as $node) {
            $node->set_source_context($source);
        }
    }
    public function get_source_context(): ?Source
    {
        return $this->source_context;
    }
}