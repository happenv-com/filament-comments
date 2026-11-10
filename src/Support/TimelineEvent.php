<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Support;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Htmlable;

/**
 * One event shown between the comments of a list.
 *
 * `$content` is rendered as given: a string is escaped, an Htmlable is trusted, so whatever builds the
 * HTML escapes what it puts in it.
 */
final readonly class TimelineEvent
{
    public function __construct(
        public string $key,
        public CarbonInterface $at,
        public Htmlable | string $content,
    ) {}
}
