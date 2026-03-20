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
 * Lazy loads the runtime implementations for a Twig element.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class Factory_Runtime_Loader implements Runtime_Loader_Interface
{
    /**
     * @param array $map An array where keys are class names and values factory callables
     */
    public function __construct(private array $map = [])
    {
    }
    public function load(string $class)
    {
        if (!isset($this->map[$class])) {
            return null;
        }
        $runtime_factory = $this->map[$class];
        return $runtime_factory();
    }
}