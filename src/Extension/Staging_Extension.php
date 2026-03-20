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
namespace Twig\Extension;

use Twig\Node_Visitor\Node_Visitor_Interface;
use Twig\Token_Parser\Token_Parser_Interface;
use Twig\Twig_Filter;
use Twig\Twig_Function;
use Twig\Twig_Test;
/**
 * Used by \Twig\Environment as a staging area.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class Staging_Extension extends Abstract_Extension
{
    private array $functions = [];
    private array $filters = [];
    private array $visitors = [];
    private array $token_parsers = [];
    private array $tests = [];
    public function add_function(Twig_Function $function): void
    {
        if (isset($this->functions[$function->get_name()])) {
            throw new \LogicException(\sprintf('Function "%s" is already registered.', $function->get_name()));
        }
        $this->functions[$function->get_name()] = $function;
    }
    public function get_functions(): array
    {
        return $this->functions;
    }
    public function add_filter(Twig_Filter $filter): void
    {
        if (isset($this->filters[$filter->get_name()])) {
            throw new \LogicException(\sprintf('Filter "%s" is already registered.', $filter->get_name()));
        }
        $this->filters[$filter->get_name()] = $filter;
    }
    public function get_filters(): array
    {
        return $this->filters;
    }
    public function add_node_visitor(Node_Visitor_Interface $visitor): void
    {
        $this->visitors[] = $visitor;
    }
    public function get_node_visitors(): array
    {
        return $this->visitors;
    }
    public function add_token_parser(Token_Parser_Interface $parser): void
    {
        if (isset($this->token_parsers[$parser->get_tag()])) {
            throw new \LogicException(\sprintf('Tag "%s" is already registered.', $parser->get_tag()));
        }
        $this->token_parsers[$parser->get_tag()] = $parser;
    }
    public function get_token_parsers(): array
    {
        return $this->token_parsers;
    }
    public function add_test(Twig_Test $test): void
    {
        if (isset($this->tests[$test->get_name()])) {
            throw new \LogicException(\sprintf('Test "%s" is already registered.', $test->get_name()));
        }
        $this->tests[$test->get_name()] = $test;
    }
    public function get_tests(): array
    {
        return $this->tests;
    }
}