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
namespace Twig\Profiler\Node_Visitor;

use Twig\Environment;
use Twig\Node\Block_Node;
use Twig\Node\Body_Node;
use Twig\Node\Macro_Node;
use Twig\Node\Module_Node;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node_Visitor\Node_Visitor_Interface;
use Twig\Profiler\Node\Enter_Profile_Node;
use Twig\Profiler\Node\Leave_Profile_Node;
use Twig\Profiler\Profile;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Profiler_Node_Visitor implements Node_Visitor_Interface
{
    private readonly string $var_name;
    public function __construct(private readonly string $extension_name)
    {
        $this->var_name = \sprintf('__internal_%s', hash('xxh128', $extension_name));
    }
    public function enter_node(Node $node, Environment $env): Node
    {
        return $node;
    }
    public function leave_node(Node $node, Environment $env): \Twig\Node\Node
    {
        if ($node instanceof Module_Node) {
            $node->set_node('display_start', new Nodes([new Enter_Profile_Node($this->extension_name, Profile::TEMPLATE, $node->get_template_name(), $this->var_name), $node->get_node('display_start')]));
            $node->set_node('display_end', new Nodes([new Leave_Profile_Node($this->var_name), $node->get_node('display_end')]));
        } elseif ($node instanceof Block_Node) {
            $node->set_node('body', new Body_Node([new Enter_Profile_Node($this->extension_name, Profile::BLOCK, $node->get_attribute('name'), $this->var_name), $node->get_node('body'), new Leave_Profile_Node($this->var_name)]));
        } elseif ($node instanceof Macro_Node) {
            $node->set_node('body', new Body_Node([new Enter_Profile_Node($this->extension_name, Profile::MACRO, $node->get_attribute('name'), $this->var_name), $node->get_node('body'), new Leave_Profile_Node($this->var_name)]));
        }
        return $node;
    }
    public function get_priority(): int
    {
        return 0;
    }
}