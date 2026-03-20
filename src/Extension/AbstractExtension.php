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

abstract class Abstract_Extension implements Last_Modified_Extension_Interface
{
    public function get_token_parsers()
    {
        return [];
    }
    public function get_node_visitors()
    {
        return [];
    }
    public function get_filters()
    {
        return [];
    }
    public function get_tests()
    {
        return [];
    }
    public function get_functions()
    {
        return [];
    }
    public function get_operators()
    {
        return [[], []];
    }
    public function get_expression_parsers(): array
    {
        return [];
    }
    public function get_last_modified(): int
    {
        $filename = (new \ReflectionClass($this))->get_file_name();
        if (!is_file($filename)) {
            return 0;
        }
        $last_modified = filemtime($filename);
        // Track modifications of the runtime class if it exists and follows the naming convention
        if (str_ends_with($filename, 'Extension.php') && is_file($filename = substr($filename, 0, -13) . 'Runtime.php')) {
            return max($last_modified, filemtime($filename));
        }
        return $last_modified;
    }
}