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
namespace Twig\Node\Expression\Binary;

use Twig\Compiler;
use Twig\Error\Syntax_Error;
use Twig\Node\Expression\Abstract_Expression;
use Twig\Node\Expression\Constant_Expression;
use Twig\Node\Expression\Return_Bool_Interface;
use Twig\Node\Node;
class Matches_Binary extends Abstract_Binary implements Return_Bool_Interface
{
    public function __construct(Node $left, Node $right, int $lineno)
    {
        if (!$left instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.24', 'Passing a "%s" instance to "%s()" first argument is deprecated, pass an "AbstractExpression" instance instead.', $left::class, __METHOD__);
        }
        if (!$right instanceof Abstract_Expression) {
            trigger_deprecation('twig/twig', '3.24', 'Passing a "%s" instance to "%s()" second argument is deprecated, pass an "AbstractExpression" instance instead.', $right::class, __METHOD__);
        }
        if ($right instanceof Constant_Expression) {
            $regexp = $right->get_attribute('value');
            set_error_handler(static fn($t, $m) => throw new Syntax_Error(\sprintf('Regexp "%s" passed to "matches" is not valid: %s.', $regexp, substr($m, 14)), $lineno));
            try {
                preg_match($regexp, '');
            } finally {
                restore_error_handler();
            }
        }
        parent::__construct($left, $right, $lineno);
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('CoreExtension::matches(')->subcompile($this->get_node('right'))->raw(', ')->subcompile($this->get_node('left'))->raw(')');
    }
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('');
    }
}