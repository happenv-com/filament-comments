<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Contracts;

use Carbon\CarbonInterface;
use Happenv\FilamentComments\Support\TimelineEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * Things that happened to the record, shown between its comments in the order they happened: a status
 * changed, someone was assigned, a document was sent. They are not comments: nobody writes, quotes or
 * links to them, and they are never counted as one.
 *
 * The list asks for the events of one page at a time, by the moments the page's comments span.
 */
interface ProvidesTimelineEvents
{
    /**
     * The events that happened at or after `$from` and before `$until`; a null bound is open.
     *
     * @return iterable<TimelineEvent>
     */
    public function between(Model $record, ?CarbonInterface $from, ?CarbonInterface $until): iterable;
}
