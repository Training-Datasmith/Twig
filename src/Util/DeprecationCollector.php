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
namespace Twig\Util;

use Twig\Environment;
use Twig\Error\Syntax_Error;
use Twig\Source;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Deprecation_Collector
{
    public function __construct(private readonly Environment $twig)
    {
    }
    /**
     * Returns deprecations for templates contained in a directory.
     *
     * @param string $dir A directory where templates are stored
     * @param string $ext Limit the loaded templates by extension
     *
     * @return array An array of deprecations
     */
    public function collect_dir(string $dir, string $ext = '.twig'): array
    {
        $iterator = new \Regex_Iterator(new \Recursive_Iterator_Iterator(new \Recursive_Directory_Iterator($dir), \Recursive_Iterator_Iterator::LEAVES_ONLY), '{' . preg_quote($ext) . '$}');
        return $this->collect(new Template_Dir_Iterator($iterator));
    }
    /**
     * Returns deprecations for passed templates.
     *
     * @param \Traversable $iterator An iterator of templates (where keys are template names and values the contents of the template)
     *
     * @return array An array of deprecations
     */
    public function collect(\Traversable $iterator): array
    {
        $deprecations = [];
        set_error_handler(static function ($type, $msg) use (&$deprecations): bool {
            if (\E_USER_DEPRECATED === $type) {
                $deprecations[] = $msg;
            }
            return false;
        });
        foreach ($iterator as $name => $contents) {
            try {
                $this->twig->parse($this->twig->tokenize(new Source($contents, $name)));
            } catch (Syntax_Error) {
                // ignore templates containing syntax errors
            }
        }
        restore_error_handler();
        return $deprecations;
    }
}