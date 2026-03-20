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
namespace Twig\Runtime_Loader;

/**
 * Creates runtime implementations for Twig elements (filters/functions/tests).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface Runtime_Loader_Interface
{
    /**
     * Creates the runtime implementation of a Twig element (filter/function/test).
     *
     * @return object|null The runtime instance or null if the loader does not know how to create the runtime for this class
     */
    public function load(string $class);
}