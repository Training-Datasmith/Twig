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
namespace Twig\Node\Expression;

/**
 * @internal
 *
 * To be removed in 4.0
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait Support_Defined_Test_Deprecation_Trait
{
    public function get_attribute($name, $default = null)
    {
        if ('is_defined_test' === $name) {
            trigger_deprecation('twig/twig', '3.21', 'The "is_defined_test" attribute is deprecated, call "isDefinedTestEnabled()" instead.');
            return $this->is_defined_test_enabled();
        }
        return parent::get_attribute($name, $default);
    }
    public function set_attribute(string $name, $value): void
    {
        if ('is_defined_test' === $name) {
            trigger_deprecation('twig/twig', '3.21', 'The "is_defined_test" attribute is deprecated, call "enableDefinedTest()" instead.');
            $this->defined_test = (bool) $value;
        } else {
            parent::set_attribute($name, $value);
        }
    }
}