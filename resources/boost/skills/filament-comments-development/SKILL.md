---
name: filament-comments-development
description: >-
  Add and customize polymorphic, paginated comment lists in Filament 4/5 schemas with
  happenv-com/filament-comments. Use when adding the HasComments trait or a Comments::make() component
  to an infolist or form, configuring it (formLocation, paginationType, commentFormat, sortColumn,
  canComment, mentionProviders, timelineEvents), writing a ProvidesTimelineEvents, ProvidesMentions,
  SavesComment, ConfiguresCommentForm or ConfiguresCommentItem class, extending the Comment item
  component, swapping the comment or author model (comment_model / author_model config, UUID keys),
  or testing the CommentsList Livewire component and the Comment factory.
---

# Filament Comments

A `Comments` schema component that shows a paginated comment list with a rich-text (or Markdown) form for the schema's current record. The component renders a nested Livewire component, `CommentsList`, and hands it its whole configuration as one validated, immutable `CommentsSettings` object in a **locked** Livewire property. That is why every extension point is a **class name implementing a contract**, never a closure doing the work, and why every setting is evaluated **once, when the list is mounted**.

## When to use this skill

- Making a model commentable, or adding a second list (internal notes) to the same record.
- Adding the comments list to a resource's infolist or form and configuring it.
- Showing record events (status changes, assignments) between comments.
- Replacing how a comment is saved, how the form or a single comment looks, or the mention providers.
- Writing tests for a page or action that uses the comments.

## Installation

```bash
composer require happenv-com/filament-comments
php artisan vendor:publish --tag=happenv-filament-comments-migrations
php artisan migrate
```

Edit the published migration **before** running it when needed: `nullableUuidMorphs('commentable')` / `nullableUlidMorphs()` for UUID/ULID commentable models, `$table->uuid('id')->primary()` for UUID comment keys (plus a custom `comment_model`), and the `author_id` foreign key when authors are not in `users`.

The views use Tailwind classes; add them to the panel's custom theme CSS:

```css
@source '../../../../vendor/happenv-com/filament-comments/resources/**/*.blade.php';
```

Config (`--tag=happenv-filament-comments-config`, file `config/filament-comments.php`):

- `author_model` (default `null` = `auth.providers.users.model`): model behind `Comment::author()` and the bundled `UserMentionProvider`. A `LogicException` is thrown if neither resolves to an Eloquent model.
- `comment_model` (default `null` = `Happenv\FilamentComments\Models\Comment`): must extend that class, else `LogicException`.

## Making a model commentable

```php
use Happenv\FilamentComments\Concerns\HasComments;
use Happenv\FilamentComments\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Ticket extends Model
{
    use HasComments; // adds comments(): MorphMany

    // A second, independent list. The return type is REQUIRED.
    public function internalNotes(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->where('content', 'like', '%[internal]%');
    }
}
```

The relationship method must declare a `HasOneOrMany` return type (`MorphMany`, `HasMany`), or be registered with `resolveRelationUsing()`; otherwise mounting throws `InvalidArgumentException`. This guard stops the browser-supplied name from calling an arbitrary method. If you set `comment_model`, use that class in your own relationships too.

## Adding the component

```php
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Filament\MentionProviders\UserMentionProvider;

public static function infolist(Schema $schema): Schema
{
    return $schema->components([
        Comments::make(), // relationship `comments`

        Comments::make('internalNotes')
            ->formLocation(CommentFormLocation::Below)             // Above (default) | Below
            ->paginationType(CommentsPaginationType::Cursor)       // Simple (default) | Standard | Cursor
            ->paginationLocation(CommentsPaginationLocation::Both) // Below (default) | Above | Both | None
            ->paginationOptions([10, 25, 50])                      // positive ints, at least one
            ->paginationDefaultPerPage(25)                         // must be one of the options
            ->commentFormat(CommentFormat::Markdown)               // Html (default, RichEditor) | Markdown
            ->sortColumn('created_at')                             // newest first, then by primary key
            ->commentItemContentFieldName('content')
            ->mentionProviders([UserMentionProvider::class])       // Html format only; adds to the list
            ->canComment(fn (Ticket $record): bool => auth()->user()->can('comment', $record))
            ->visible(fn (): bool => auth()->user()->isStaff()),
    ]);
}
```

- Every setter takes a value or a closure (Filament injections: `$record`, `$livewire`, ...). Closures run once, at mount.
- The component hides itself while the schema has no record (create pages).
- `canComment(false)` hides the form and the quote action and makes `submitComment` a no-op. It does **not** hide the list: use `visible()` / `hidden()` for who may read.
- Submitting also needs a logged-in user (`Auth::user()`) and non-empty content; otherwise a danger notification, nothing saved.
- With `formLocation(Below)` each page is shown oldest to newest (reads towards the form).

## Timeline events between comments

```php
use Carbon\CarbonInterface;
use Happenv\FilamentComments\Contracts\ProvidesTimelineEvents;
use Happenv\FilamentComments\Support\TimelineEvent;
use Illuminate\Database\Eloquent\Model;

final class TicketStatusEvents implements ProvidesTimelineEvents
{
    public function between(Model $record, ?CarbonInterface $from, ?CarbonInterface $until): iterable
    {
        return $record->statusChanges()
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))  // inclusive
            ->when($until, fn ($query) => $query->where('created_at', '<', $until)) // exclusive
            ->get()
            ->map(fn ($change): TimelineEvent => new TimelineEvent(
                key: (string) $change->id,   // string, unique within the list (used in wire:key)
                at: $change->created_at,
                content: __('Status changed to :status', ['status' => $change->status]),
            ));
    }
}

Comments::make()->timelineEvents(TicketStatusEvents::class);
```

- The list asks once per page; respect both bounds exactly or events repeat or vanish across pages. `null` means open.
- A string `content` is escaped; an `Htmlable` is output as-is, so escape user data you put into it.
- The provider is resolved from the container (`app($class)`), so constructor injection works.

## Mentions and rendering

A mention provider implements `ProvidesMentions` (a static `make(): MentionProvider`):

```php
use Filament\Forms\Components\RichEditor\MentionProvider;
use Happenv\FilamentComments\Contracts\ProvidesMentions;

final class TeamMentionProvider implements ProvidesMentions
{
    public static function make(): MentionProvider
    {
        return MentionProvider::make('#')
            ->getSearchResultsUsing(fn (string $search): array => Team::query()
                ->where('name', 'like', "%{$search}%")->limit(10)->pluck('name', 'id')->all())
            ->getLabelsUsing(fn (array $ids): array => Team::query()
                ->whereIn('id', $ids)->pluck('name', 'id')->all());
    }
}
```

The comment model registers `content` as Filament rich content with `UserMentionProvider` mentions. To configure it per commentable type, add `setUpCommentsRichContent(Comment $comment): Closure` to the commentable model; it is called on a **new, empty instance** of that model (not the commented record) and replaces the default mentions:

```php
public function setUpCommentsRichContent(Comment $comment): Closure
{
    return fn (RichContentAttribute $attribute): RichContentAttribute => $attribute
        ->mentions([TeamMentionProvider::make()]);
}
```

## Customizing (contracts)

| Setter | Contract / base | Default |
| --- | --- | --- |
| `saveAction()` | `Contracts\SavesComment` | `Actions\SaveCommentAction` |
| `formSchema()` | `Contracts\ConfiguresCommentForm` | `Filament\Schemas\CommentFormSchema` |
| `itemSchema()` | `Contracts\ConfiguresCommentItem` | `Filament\Schemas\CommentItemSchema` |
| `commentItemComponent()` | extends `Filament\Components\Comment` | `Filament\Components\Comment` |
| `mentionProvider()` / `mentionProviders()` | `Contracts\ProvidesMentions` | none |
| `timelineEvents()` | `Contracts\ProvidesTimelineEvents` | none |

Each accepts a class name or a closure **returning a class name**. A class that does not implement its contract throws `InvalidArgumentException` when the settings are built.

```php
use Happenv\FilamentComments\Actions\SaveCommentAction;
use Happenv\FilamentComments\Contracts\SavesComment;
use Happenv\FilamentComments\Support\CommentsSettings;

final class SaveAndNotify implements SavesComment
{
    public function __invoke(Model $record, array $data, Authenticatable $author, CommentsSettings $settings): Model
    {
        $comment = app(SaveCommentAction::class)($record, $data, $author, $settings);
        $record->owner->notify(new NewComment($comment));

        return $comment;
    }
}
```

- Form: extend `CommentFormSchema` and override `htmlFormatInput(string $contentField, array $mentions): RichEditor` or `markdownFormatInput(string $contentField): MarkdownEditor`. A from-scratch `ConfiguresCommentForm::configure(Schema $schema, CommentsSettings $settings)` must still have an action calling the list's `submitComment` method and use `$settings->contentField` as the field name.
- Item: `ConfiguresCommentItem::configure(Schema $schema, CommentsSettings $settings)` usually returns `$settings->itemComponent::make($settings->contentField)->...`.
- Item component parts: `headerComponent()`, `authorComponent()`, `createdAtComponent()`, `contentComponent()`, `footerComponent()` take a component, a closure returning one, or `false` to remove it; `markdown()` switches the content renderer. For new defaults, subclass `Comment` and override `getDefault{Header,Author,CreatedAt,Content,Footer}Component()`, `getQuoteAction()` or `getShareAction()`.
- Icons: register `Happenv\FilamentComments\View\CommentsIconAlias::FORM_SUBMIT`, `COMMENT_QUOTE`, `COMMENT_SHARE` with `FilamentIcon::register([...])`.

## Deep links, query string and browser events

- Page: `{relationship}_page` (e.g. `comments_page`); deep link: `?{relationship}_comment_id=42`. Opening a deep link jumps to the page holding the comment (all pagination types) and highlights it.
- Browser events: `comment-added` (`relationship`) after a save, `highlight-comment` (`commentId`, `relationship`) when a deep link opens. Filter on `$event.detail.relationship` when a page has two lists.

## Gotchas

- **Swap the comment model with the `comment_model` config, not a container binding.** `HasComments` resolves the class from config (`Support\CommentModel::resolve()`); `$this->app->bind(Comment::class, ...)` has no effect on it.
- Two components on one page must use **different relationship names**: the Livewire key, page name, deep-link parameter and form id all derive from it.
- `sortColumn()`, `commentItemContentFieldName()` and the relationship name must be plain identifiers (`/^[A-Za-z_][A-Za-z0-9_]*$/`). A content field other than `content` needs its own column, and the model only registers `content` as rich content.
- The author model needs a `name` column: the default author entry shows `author.name` and `UserMentionProvider` searches `name`. Override `authorComponent()` / write a provider otherwise.
- A closure that reads changing state (permissions, feature flags) is frozen for the life of the mounted list; it is re-evaluated on the next page load, not on each request.
- Do not mount `CommentsList` with hand-made props; build its settings with `Comments::make('rel')->...->toSettings()`.
- 1.x code you may find (save action with `$relationName, $commentItemContentFieldName`, `configure(Schema, array, string, CommentFormat)`, public properties on `CommentsList`) does not work on 2.x; use the signatures above.
- Requirements: PHP 8.3+, Laravel 12/13, Filament `^4.5` or `^5.0`.

## Testing

```php
use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Livewire\CommentsList;
use Happenv\FilamentComments\Models\Comment;
use Livewire\Livewire;

$comment = Comment::factory()
    ->for($ticket, 'commentable')
    ->withAuthor($user)                 // without it, the author model's own factory is used
    ->create(['content' => '<p>Looks good</p>']);

$this->actingAs($user);

Livewire::test(CommentsList::class, [
    'record' => $ticket,
    'settings' => Comments::make()->saveAction(SaveAndNotify::class)->toSettings(),
])
    ->fillForm(['content' => '<p>Hello</p>'])
    ->call('submitComment')
    ->assertHasNoFormErrors()
    ->assertDispatched('comment-added', relationship: 'comments');

// Deep link
$settings = Comments::make()->toSettings();
Livewire::withQueryParams([$settings->commentIdParameter() => $comment->getKey()])
    ->test(CommentsList::class, ['record' => $ticket, 'settings' => $settings])
    ->assertDispatched('highlight-comment', commentId: $comment->getKey(), relationship: 'comments');
```

`Comment::factory()` always builds `Happenv\FilamentComments\Models\Comment`; a custom `comment_model` (e.g. with `HasUuids`) needs its own factory. When mounting `CommentsList` directly, pass plain values to the setters; closures that inject `$record` or `$livewire` need the component inside a schema, so test those through the resource page (`Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])`).
