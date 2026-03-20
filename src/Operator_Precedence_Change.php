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

use Twig\Expression_Parser\Precedence_Change;
/**
 * Represents a precedence change.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since Twig 1.20 Use Twig\ExpressionParser\PrecedenceChange instead
 */
class Operator_Precedence_Change extends Precedence_Change
{
    public function __construct(string $package, string $version, int $new_precedence)
    {
        trigger_deprecation('twig/twig', '3.21', 'The "%s" class is deprecated since Twig 3.21. Use "%s" instead.', self::class, Precedence_Change::class);
        parent::__construct($package, $version, $new_precedence);
    }
}