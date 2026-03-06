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

use Twig\Error\SyntaxError;
use Twig\Node\DeprecatedNode;
use Twig\Token;

/**
 * Deprecates a section of a template.
 *
 *    {% deprecated 'The "base.twig" template is deprecated, use "layout.twig" instead.' %}
 *    {% extends 'layout.html.twig' %}
 *
 *    {% deprecated 'The "base.twig" template is deprecated, use "layout.twig" instead.' package="foo/bar" version="1.1" %}
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 *
 * @internal
 */
final class DeprecatedTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): \Twig\Node\DeprecatedNode
    {
        $stream = $this->parser->getStream();
        $expr = $this->parser->parseExpression();
        $node = new DeprecatedNode($expr, $token->getLine());

        while ($stream->test(Token::NAME_TYPE)) {
            $k = $stream->getCurrent()->getValue();
            $stream->next();
            $stream->expect(Token::OPERATOR_TYPE, '=');

            match ($k) {
                'package' => $node->setNode('package', $this->parser->parseExpression()),
                'version' => $node->setNode('version', $this->parser->parseExpression()),
                default => throw new SyntaxError(\sprintf('Unknown "%s" option.', $k), $stream->getCurrent()->getLine(), $stream->getSourceContext()),
            };
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return $node;
    }

    public function getTag(): string
    {
        return 'deprecated';
    }
}
