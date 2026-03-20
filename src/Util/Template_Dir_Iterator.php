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

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Template_Dir_Iterator extends \Iterator_Iterator
{
    /**
     * @return string
     */
    #[\Return_Type_Will_Change]
    public function current()
    {
        return file_get_contents(parent::current());
    }
    /**
     * @return string
     */
    #[\Return_Type_Will_Change]
    public function key()
    {
        return (string) parent::key();
    }
}