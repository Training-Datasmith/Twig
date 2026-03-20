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
namespace Twig;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Deprecated_Callable_Info
{
    private string $type;
    private string $name;
    public function __construct(private readonly string $package, private readonly string $version, private readonly ?string $alt_name = null, private readonly ?string $alt_package = null, private readonly ?string $alt_version = null)
    {
    }
    public function set_type(string $type): void
    {
        $this->type = $type;
    }
    public function set_name(string $name): void
    {
        $this->name = $name;
    }
    public function trigger_deprecation(?string $file = null, ?int $line = null): void
    {
        $message = \sprintf('Twig %s "%s" is deprecated', ucfirst($this->type), $this->name);
        if ($this->alt_name) {
            $message .= \sprintf('; use "%s"', $this->alt_name);
            if ($this->alt_package) {
                $message .= \sprintf(' from the "%s" package', $this->alt_package);
            }
            if ($this->alt_version) {
                $message .= \sprintf(' (available since version %s)', $this->alt_version);
            }
            $message .= ' instead';
        }
        if ($file) {
            $message .= \sprintf(' in %s', $file);
            if ($line) {
                $message .= \sprintf(' at line %d', $line);
            }
        }
        $message .= '.';
        trigger_deprecation($this->package, $this->version, $message);
    }
}