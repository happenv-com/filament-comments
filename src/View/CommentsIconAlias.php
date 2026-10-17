<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\View;

/**
 * Icon aliases this package resolves, so a host can re-icon the comments list globally. Register them
 * exactly as Filament's own:
 *
 * ```php
 * use Filament\Support\Facades\FilamentIcon;
 * use Happenv\FilamentComments\View\CommentsIconAlias;
 *
 * FilamentIcon::register([
 *     CommentsIconAlias::FORM_SUBMIT => Phosphor::PaperPlaneTilt,
 * ]);
 * ```
 *
 * The string values are part of the public contract — a host registers against them — so they may
 * not be renamed without a breaking release. They share the package's translation namespace, which
 * is what keeps them from colliding with another package's aliases.
 *
 * Per-instance modifiers (`quoteAction()`, `shareAction()`, …) still win over a registered alias: the
 * alias is the default, the modifier is the exception.
 */
class CommentsIconAlias
{
    /** The submit button of the comment form. Default: Heroicon::PaperAirplane. */
    const FORM_SUBMIT = 'happenv-filament-comments::form.submit';

    /** The "quote" action on a comment. Default: Heroicon::ChatBubbleBottomCenterText. */
    const COMMENT_QUOTE = 'happenv-filament-comments::comment.quote';

    /** The "copy link" action on a comment. Default: Heroicon::Link. */
    const COMMENT_SHARE = 'happenv-filament-comments::comment.share';
}
