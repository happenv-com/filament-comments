# Filament Comments

[![Latest Version](https://img.shields.io/github/v/release/happenv-com/filament-comments?style=flat-square&label=version)](https://github.com/happenv-com/filament-comments/releases)
[![Tests](https://img.shields.io/github/actions/workflow/status/happenv-com/filament-comments/tests.yml?label=tests&style=flat-square)](https://github.com/happenv-com/filament-comments/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/happenv-com/filament-comments/phpstan.yml?label=phpstan&style=flat-square)](https://github.com/happenv-com/filament-comments/actions/workflows/phpstan.yml)
[![Quality](https://img.shields.io/github/actions/workflow/status/happenv-com/filament-comments/quality.yml?label=code%20quality&style=flat-square)](https://github.com/happenv-com/filament-comments/actions/workflows/quality.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/happenv-com/filament-comments.svg?style=flat-square)](https://packagist.org/packages/happenv-com/filament-comments)
[![License](https://img.shields.io/github/license/happenv-com/filament-comments.svg?style=flat-square)](LICENSE.md)

Polymorphic, paginated comments for Filament 4 and 5. Drop a `Comments` component into any infolist or form schema and
the current record gets a comment list with a rich-text (or Markdown) form, quoting, shareable deep links and
pagination.

```php
use Happenv\FilamentComments\Filament\Components\Comments;

$schema->components([
    Comments::make(), // uses the record's `comments` relationship
]);
```

## Key features

- **Comments on any record.** Add the `HasComments` trait to a model and a `Comments` component to its schema — see [Usage](#usage).
- **Rich text or Markdown.** Comments are written in Filament's rich editor, with optional mentions, or in the Markdown editor — see [Comment format](#comment-format).
- **Quoting.** One click quotes a comment into the form, as a blockquote in rich text or as `>` lines in Markdown.
- **Shareable deep links.** Every comment has a "copy link" action; opening the link jumps to the page containing the comment and highlights it — see [Deep links](#deep-links).
- **Three pagination types.** Simple, standard (with page numbers) or cursor pagination, with a per-page selector above, below or on both sides of the list — see [Pagination](#pagination).
- **Several lists per record.** Each relationship (e.g. `comments` and `internalNotes`) gets its own independent list on the same page.
- **Safe by default.** The list's configuration is validated once and stored in a locked Livewire property, so the browser cannot change it — see [Extending](#extending).
- **Replaceable parts.** The save action, the form, the comment item and every part of it are classes you can swap — see [Customizing](#customizing).

## Requirements

| Package  | Versions                         |
|----------|----------------------------------|
| PHP      | 8.3 – 8.5                        |
| Laravel  | 12, 13                           |
| Filament | 4 (`^4.5.0`), 5 (`^5.0`)         |
| Livewire | 3 (Filament 4), 4 (Filament 5)   |

## Installation

Install the package via Composer:

```bash
composer require happenv-com/filament-comments
```

Publish and run the migration that creates the `comments` table:

```bash
php artisan vendor:publish --tag=happenv-filament-comments-migrations
php artisan migrate
```

The table has an auto-incrementing `id`, a polymorphic `commentable` relation created with `nullableMorphs()` (so
`commentable_id` is an integer column), an `author_id` foreign key to the `users` table, a `content` text column and
timestamps. Edit the published migration before running it if your models use UUID or ULID keys (see
[UUID or ULID keys](#uuid-or-ulid-keys)) or your authors are not stored in `users`.

> [!IMPORTANT]
> If you have not set up a custom theme and are using Filament Panels, follow the instructions in the Filament docs
> ([4.x](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme),
> [5.x](https://filamentphp.com/docs/5.x/styling/overview#creating-a-custom-theme)) first.

Add the package's views to your theme's CSS file, so Tailwind generates the classes they use:

```css
@source '../../../../vendor/happenv-com/filament-comments/resources/**/*.blade.php';
```

## Configuration

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=happenv-filament-comments-config
```

This is the content of the published config file, `config/filament-comments.php`:

```php
return [
    /*
     * Model of comment authors. Defaults to the model of the "users" auth provider.
     */
    'author_model' => null,

    /*
     * Model of comments. Must extend Happenv\FilamentComments\Models\Comment,
     * e.g. to add HasUuids together with a matching migration.
     */
    'comment_model' => null,
];
```

| Key            | Default | What it does |
|----------------|---------|--------------|
| `author_model` | `null`  | Eloquent model of comment authors (`Comment::author()`, the default mention provider). `null` falls back to `auth.providers.users.model`. |
| `comment_model` | `null` | Eloquent model of comments, used by `HasComments`. Must extend `Happenv\FilamentComments\Models\Comment`. `null` uses the package's own model. |

The author model does not depend on the logged-in user. If neither setting points to an Eloquent model, a
`LogicException` is thrown. For example:

```php
'author_model' => App\Models\Admin::class,
```

Optionally, publish the views and translations:

```bash
php artisan vendor:publish --tag=happenv-filament-comments-views
php artisan vendor:publish --tag=happenv-filament-comments-translations
```

### UUID or ULID keys

The migration uses standard auto-incrementing keys. If your models use UUIDs (or ULIDs), change the published
migration before running it:

- **Commentable models with UUID keys** — replace `nullableMorphs('commentable')` with `nullableUuidMorphs('commentable')`
  (or `nullableUlidMorphs()`).
- **UUID keys for the comments themselves** — replace `$table->id()` with `$table->uuid('id')->primary()`, and point
  `comment_model` to your own model that adds `HasUuids`:

```php
namespace App\Models;

use Happenv\FilamentComments\Models\Comment as BaseComment;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Comment extends BaseComment
{
    use HasUuids;
}
```

```php
// config/filament-comments.php
'comment_model' => App\Models\Comment::class,
```

Relationships you define yourself (such as `internalNotes()` below) should use the same model.

## Usage

### Make a model commentable

```php
use Happenv\FilamentComments\Concerns\HasComments;

class Issue extends Model
{
    use HasComments;
}
```

`HasComments` adds a `comments()` morph-many relationship. You can define more relationships on the same model (for
example `internalNotes()`); every relationship must declare its return type (`MorphMany`, `HasMany`, ...):

```php
use Happenv\FilamentComments\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

public function internalNotes(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable')
        ->where('content', 'like', '%[internal]%');
}
```

### Add the component to a schema

```php
use Happenv\FilamentComments\Filament\Components\Comments;

public static function infolist(Schema $schema): Schema
{
    return $schema->components([
        Comments::make(), // uses the `comments` relationship
    ]);
}
```

`Comments::make()` takes the name of the relationship, `comments` by default. Two components on the same page (e.g.
`Comments::make('comments')` and `Comments::make('internalNotes')`) render two independent lists.

The component is hidden while the schema has no record (e.g. on a create page). It is a regular schema component, so
the usual methods such as `visible()` and `hidden()` work too.

### Component options

Every setting accepts a value or a closure returning it. Closures are evaluated once, when the list is mounted, with
the usual Filament injections (`$record`, `$livewire`, ...).

| Method | Default | What it does |
|--------|---------|--------------|
| `formLocation()` | `CommentFormLocation::Above` | Where the comment form is shown: `Above` or `Below` the list. |
| `paginationType()` | `CommentsPaginationType::Simple` | `Simple`, `Standard` or `Cursor` pagination. |
| `paginationLocation()` | `CommentsPaginationLocation::Below` | Where the pagination is shown: `Above`, `Below`, `Both` or `None`. |
| `paginationOptions()` | `[10, 20, 50]` | Page sizes the user can pick. |
| `paginationDefaultPerPage()` | `20` | Initial page size; must be one of the options. |
| `commentFormat()` | `CommentFormat::Html` | `Html` (rich editor) or `Markdown` (Markdown editor). |
| `sortColumn()` | `'created_at'` | Column the list is sorted by, newest first. |
| `commentItemContentFieldName()` | `'content'` | Attribute of the comment model that holds its content. |
| `canComment()` | `true` | Whether the form and the quote action are shown and comments can be submitted. |
| `mentionProvider()` / `mentionProviders()` | none | Mention providers of the rich editor. |
| `saveAction()` | `SaveCommentAction::class` | Class that saves a new comment. |
| `formSchema()` | `CommentFormSchema::class` | Class that builds the comment form. |
| `itemSchema()` | `CommentItemSchema::class` | Class that builds the schema of one comment. |
| `commentItemComponent()` | `Comment::class` | Schema component that renders one comment. |

All of them together:

```php
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Filament\MentionProviders\UserMentionProvider;

Comments::make('internalNotes')
    ->formLocation(CommentFormLocation::Below)              // Above (default) or Below
    ->paginationType(CommentsPaginationType::Standard)      // Simple (default), Standard or Cursor
    ->paginationLocation(CommentsPaginationLocation::Both)  // Above, Below (default), Both or None
    ->paginationOptions([10, 25, 50])                       // page sizes the user can pick
    ->paginationDefaultPerPage(25)                          // must be one of the options
    ->commentFormat(CommentFormat::Markdown)                // Html (default) or Markdown
    ->sortColumn('created_at')                              // newest first, ties broken by the primary key
    ->commentItemContentFieldName('content')                // attribute that holds the comment
    ->mentionProviders([UserMentionProvider::class])
    ->canComment(fn (Issue $record): bool => auth()->user()->can('comment', $record));
```

#### Form location

```php
Comments::make()->formLocation(CommentFormLocation::Below);
```

With the form above the list (the default), the newest comment is at the top. With the form below the list, each
page is shown oldest to newest, so the conversation reads towards the form.

#### Pagination

```php
Comments::make()
    ->paginationType(CommentsPaginationType::Cursor)
    ->paginationLocation(CommentsPaginationLocation::Above)
    ->paginationOptions([5, 10, 25])
    ->paginationDefaultPerPage(10);
```

- `paginationType()`: `Simple` (previous / next, the default), `Standard` (page numbers) or `Cursor`.
- `paginationLocation()`: `Above`, `Below` (the default), `Both` or `None`. The pagination is not rendered while the
  list is empty.
- `paginationOptions()`: the page sizes in the per-page select, `[10, 20, 50]` by default. At least one, all positive
  integers.
- `paginationDefaultPerPage()`: the initial page size, `20` by default. It must be one of the options. A page size
  sent by the browser that is not one of the options falls back to this default.

The page is kept in the query string as `{relationship}_page` (e.g. `comments_page`), so two lists on one page
paginate independently. Changing the page size, or adding a comment, goes back to the first page.

#### Sorting

```php
Comments::make()->sortColumn('updated_at');
```

Comments are sorted by this column, newest first (`created_at` by default), and then by primary key, so pages are
stable when timestamps are equal. The column name must be a plain identifier (letters, digits and underscores).

#### Comment format

```php
Comments::make()->commentFormat(CommentFormat::Markdown);
```

- `CommentFormat::Html` (default): the form uses Filament's rich editor (bold, italic, strike, link, bullet and
  ordered lists, code block and blockquote, plus mentions). Comments are rendered as HTML.
- `CommentFormat::Markdown`: the form uses Filament's Markdown editor, and comments are rendered with the Markdown
  parser.

#### Content attribute

```php
Comments::make()->commentItemContentFieldName('body');
```

The attribute of the comment model that the form writes to and the list renders, `content` by default. It must be a
plain identifier.

#### Who can comment

```php
Comments::make()->canComment(fn (Issue $record): bool => auth()->user()->can('comment', $record));
```

`true` by default. When it is `false`, the form and the quote action are hidden and the list refuses to save a
comment. Submitting a comment also requires a logged-in user and non-empty content; otherwise the user gets a
notification and nothing is saved.

#### Mentions

```php
use Happenv\FilamentComments\Filament\MentionProviders\UserMentionProvider;

Comments::make()->mentionProvider(UserMentionProvider::class);

// or several at once; both methods add to the list
Comments::make()->mentionProviders([UserMentionProvider::class, TeamMentionProvider::class]);
```

Mention providers are only used by the rich editor (`CommentFormat::Html`). None are registered by default. A
provider is a class implementing `Contracts\ProvidesMentions` — a static `make()` method returning a Filament
`MentionProvider`:

```php
use Filament\Forms\Components\RichEditor\MentionProvider;
use Happenv\FilamentComments\Contracts\ProvidesMentions;

class TeamMentionProvider implements ProvidesMentions
{
    public static function make(): MentionProvider
    {
        return MentionProvider::make('#')
            ->getSearchResultsUsing(fn (string $search): array => Team::query()
                ->where('name', 'like', "%{$search}%")
                ->pluck('name', 'id')
                ->all())
            ->getLabelsUsing(fn (array $ids): array => Team::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all());
    }
}
```

The bundled `UserMentionProvider` searches the `name` column of the [author model](#configuration) (up to 10 results)
with the `@` character.

### Deep links

Every comment has a "copy link" action that copies a link to the current page with the comment in the query string,
e.g. `?comments_comment_id=42` (the parameter is `{relationship}_comment_id`). Opening the link opens the page
containing the comment and highlights it. This works with all three pagination types.

### Rendering comment content

The comment model registers its `content` attribute as Filament rich content. By default the attribute gets the
`UserMentionProvider` mentions. A commentable model can configure the attribute itself with a
`setUpCommentsRichContent()` method (called on a new instance of the commentable model), which returns a closure that
receives the attribute. The default mentions are then not added:

```php
use Closure;
use Filament\Forms\Components\RichEditor\RichContentAttribute;
use Happenv\FilamentComments\Models\Comment;

class Issue extends Model
{
    use HasComments;

    public function setUpCommentsRichContent(Comment $comment): Closure
    {
        return fn (RichContentAttribute $attribute): RichContentAttribute => $attribute
            ->mentions([TeamMentionProvider::make()]);
    }
}
```

### Events

The list dispatches two browser events, which you can listen to with Alpine or Livewire:

| Event | Parameters | When |
|-------|------------|------|
| `comment-added` | `relationship` | After a comment is saved. |
| `highlight-comment` | `commentId`, `relationship` | When a deep link opens; the list scrolls to the comment and highlights it. |

```blade
<div x-on:comment-added.window="if ($event.detail.relationship === 'comments') $wire.$refresh()">
    ...
</div>
```

### Validation

Invalid configuration throws an `InvalidArgumentException` when the list is built:

- the default page size must be one of `paginationOptions()`, and the options must be positive integers;
- the relationship name, sort column and content field must be plain identifiers;
- extension classes must implement their contracts (see [Customizing](#customizing));
- the relationship method must exist and declare a relation return type (e.g. `: MorphMany`).

## Extending

The list is rendered by a nested Livewire component (`Livewire\CommentsList`, registered as
`happenv-filament-comments-list`). The component receives its configuration as one validated, immutable
`CommentsSettings` object, stored in a locked Livewire property, so the browser can't change it. Build the settings
from a component with `Comments::make('comments')->...->toSettings()`. For the same reason, extension points are
class names that implement a contract, not closures.

### Customizing

| Setting | Contract | Default |
| --- | --- | --- |
| `saveAction()` | `Contracts\SavesComment` | `Actions\SaveCommentAction` |
| `formSchema()` | `Contracts\ConfiguresCommentForm` | `Filament\Schemas\CommentFormSchema` |
| `itemSchema()` | `Contracts\ConfiguresCommentItem` | `Filament\Schemas\CommentItemSchema` |
| `commentItemComponent()` | extends `Filament\Components\Comment` | `Filament\Components\Comment` |
| `mentionProviders()` | `Contracts\ProvidesMentions` | none |

Each of them accepts a class name, or a closure that returns one.

#### Save action

The save action is invoked with the record, the validated form state, the logged-in author and the settings, and
returns the saved comment. A custom save action, for example:

```php
use Happenv\FilamentComments\Actions\SaveCommentAction;
use Happenv\FilamentComments\Contracts\SavesComment;
use Happenv\FilamentComments\Support\CommentsSettings;

class SaveAndNotify implements SavesComment
{
    public function __invoke(Model $record, array $data, Authenticatable $author, CommentsSettings $settings): Model
    {
        $comment = app(SaveCommentAction::class)($record, $data, $author, $settings);

        $record->owner->notify(new NewComment($comment));

        return $comment;
    }
}

Comments::make()->saveAction(SaveAndNotify::class);
```

#### Form schema

The form schema class builds the comment form. Extend `CommentFormSchema` and override `htmlFormatInput()` or
`markdownFormatInput()` to change the editor, or implement `ConfiguresCommentForm` from scratch. The form must call
the list's `submitComment` method:

```php
use Filament\Forms\Components\RichEditor;
use Happenv\FilamentComments\Filament\Schemas\CommentFormSchema;

class CompactCommentForm extends CommentFormSchema
{
    public static function htmlFormatInput(string $contentField, array $mentions): RichEditor
    {
        return parent::htmlFormatInput($contentField, $mentions)
            ->toolbarButtons(['bold', 'italic', 'link']);
    }
}

Comments::make()->formSchema(CompactCommentForm::class);
```

#### Item schema

The item schema class builds the schema of one comment. The default one renders `commentItemComponent()` for the
content field, in Markdown mode for `CommentFormat::Markdown`:

```php
use Filament\Schemas\Schema;
use Happenv\FilamentComments\Contracts\ConfiguresCommentItem;
use Happenv\FilamentComments\Support\CommentsSettings;

class CompactCommentItem implements ConfiguresCommentItem
{
    public static function configure(Schema $schema, CommentsSettings $settings): Schema
    {
        return $schema->components([
            $settings->itemComponent::make($settings->contentField)
                ->footerComponent(false),
        ]);
    }
}

Comments::make()->itemSchema(CompactCommentItem::class);
```

#### Comment item component

`Filament\Components\Comment` renders one comment: a header (author and date), the content and a footer (quote and
share actions). Every part accepts another component, a closure returning one, or `false` to remove it —
`headerComponent()`, `authorComponent()`, `createdAtComponent()`, `contentComponent()` and `footerComponent()` —
and `markdown()` switches the content to the Markdown parser. The parts are resolved lazily, so configuration applied
after `make()` takes effect.

To change the defaults for every comment, extend the component and override any of the `getDefault*Component()`
methods (header, author, created at, content, footer) or the `getQuoteAction()` / `getShareAction()` methods:

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Happenv\FilamentComments\Filament\Components\Comment;

class CommentWithEmail extends Comment
{
    public function getDefaultAuthorComponent(): Component | false | null
    {
        return TextEntry::make('author.email')->hiddenLabel();
    }
}

Comments::make()->commentItemComponent(CommentWithEmail::class);
```

#### Comment model

`HasComments` resolves the comment model from the container, so you can replace it with a subclass of
`Models\Comment` in a service provider:

```php
use Happenv\FilamentComments\Models\Comment;

public function register(): void
{
    $this->app->bind(Comment::class, App\Models\Comment::class);
}
```

## Testing your application

The comment model has a factory. Its `withAuthor()` state sets the author; without it, the factory creates an author
with the author model's own factory:

```php
use Happenv\FilamentComments\Models\Comment;

$comment = Comment::factory()
    ->for($issue, 'commentable')
    ->withAuthor($user)
    ->create(['content' => '<p>Looks good</p>']);
```

## Translations

The package ships these languages:

| Language (`code`) |
|-------------------|
| English (`en`)    |
| Polish (`pl`)     |

Publish them to change the texts:

```bash
php artisan vendor:publish --tag=happenv-filament-comments-translations
```

`tests/Unit/TranslationsTest.php` checks that every language has exactly the keys English has.

## Development

```bash
composer test          # unit and feature tests
composer phpstan       # static analysis
composer cs            # fix code style: composer normalize, Rector, Pint
composer ci            # everything CI checks, locally
```

The test suite runs on Pest against Filament 4 (Livewire 3) and Filament 5 (Livewire 4), on Laravel 12 and 13.

## Upgrading

Breaking changes and how to migrate are described in [UPGRADING](UPGRADING.md) for every major version.

## Changelog

See [CHANGELOG](CHANGELOG.md) and [GitHub releases](https://github.com/happenv-com/filament-comments/releases) for what has changed recently.

## Contributing

See [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Happenv sp. z o.o.](https://happenv.com)
- [webard](https://github.com/webard)
- [All contributors](../../contributors)

## License

The MIT License (MIT). See [License File](LICENSE.md) for more information.

---

<p align="center">
    <a href="https://happenv.com">
        <img src="art/happenv-logo.png" alt="Happenv" width="400">
    </a>
</p>
