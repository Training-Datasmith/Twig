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
namespace Twig\Node_Visitor;

use Twig\Attribute\Yield_Ready;
use Twig\Environment;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Node;
/**
 * @internal to be removed in Twig 4
 */
final class Yield_Not_Ready_Node_Visitor implements Node_Visitor_Interface
{
    private array $yield_ready_nodes = [];
    public function __construct(private readonly bool $use_yield)
    {
    }
    public function enter_node(Node $node, Environment $env): Node
    {
        $class = $node::class;
        if ($node instanceof Abstract_Expression || isset($this->yield_ready_nodes[$class])) {
            return $node;
        }
        if (!$this->yield_ready_nodes[$class] = (bool) (new \ReflectionClass($class))->get_attributes(Yield_Ready::class)) {
            if ($this->use_yield) {
                throw new \LogicException(\sprintf('You cannot enable the "use_yield" option of Twig as node "%s" is not marked as ready for it; please make it ready and then flag it with the #[\Twig\Attribute\YieldReady] attribute.', $class));
            }
            trigger_deprecation('twig/twig', '3.9', 'Twig node "%s" is not marked as ready for using "yield" instead of "echo"; please make it ready and then flag it with the #[\Twig\Attribute\YieldReady] attribute.', $class);
        }
        return $node;
    }
    public function leave_node(Node $node, Environment $env): \Twig\Node\Node
    {
        return $node;
    }
    public function get_priority(): int
    {
        return 255;
    }
}