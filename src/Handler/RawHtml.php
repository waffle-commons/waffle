<?php

declare(strict_types=1);

namespace Waffle\Handler;

/**
 * Opt-in wrapper for a controller that deliberately wants to return raw,
 * unescaped HTML from {@see ControllerResponseConverter::convert()}.
 *
 * A bare `string` return is escaped by default (see
 * {@see ControllerResponseConverter::convertResult()}) because it is the
 * common case of a controller echoing back request-influenced text. A
 * controller that already trusts its markup (e.g. it was assembled from a
 * sanitized template, not from user input) opts out explicitly by wrapping
 * it in this value object instead.
 */
final readonly class RawHtml
{
    public function __construct(
        public string $html,
    ) {}
}
