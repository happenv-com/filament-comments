<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Contracts;

/**
 * Optional for a save action: names what was saved in the success notification, in place of the default
 * "Comment added". Asked right after the save, so the title follows the current locale.
 */
interface ProvidesSavedNotificationTitle
{
    public function savedNotificationTitle(): string;
}
