<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->security();

arch('source files declare strict types')
    ->expect('Happenv\FilamentComments')
    ->toUseStrictTypes();

arch('no debugging calls')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();
