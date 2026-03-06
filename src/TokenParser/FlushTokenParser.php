<?php

declare(strict_types=1);

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\TokenParser;

use Twig\Node\FlushNode;
use Twig\Token;

/**
 * Flushes the output to the client.
 *
 * @see flush()
 *
 * @internal
 */
final class FlushTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): \Twig\Node\FlushNode
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new FlushNode($token->getLine());
    }

    public function getTag(): string
    {
        return 'flush';
    }
}
