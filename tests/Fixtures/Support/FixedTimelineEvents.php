<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Support;

use Carbon\CarbonInterface;
use Happenv\FilamentComments\Contracts\ProvidesTimelineEvents;
use Happenv\FilamentComments\Support\TimelineEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * Hands back the events a test put in `$events`, within the bounds the list asks for.
 */
final class FixedTimelineEvents implements ProvidesTimelineEvents
{
    /**
     * @var list<TimelineEvent>
     */
    public static array $events = [];

    /**
     * Every window the list asked for, in order.
     *
     * @var list<array{from: CarbonInterface|null, until: CarbonInterface|null}>
     */
    public static array $asked = [];

    public function between(Model $record, ?CarbonInterface $from, ?CarbonInterface $until): iterable
    {
        self::$asked[] = ['from' => $from, 'until' => $until];

        foreach (self::$events as $event) {
            if ((! $from instanceof CarbonInterface || $event->at->greaterThanOrEqualTo($from)) && (! $until instanceof CarbonInterface || $event->at->lessThan($until))) {
                yield $event;
            }
        }
    }
}
