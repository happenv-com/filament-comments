# Filament Comments

Polymorphic, paginated comments for Filament 4 and 5. Drop a `Comments` component into any infolist or form schema and
the current record gets a comment list with a rich-text (or Markdown) form, quoting, shareable deep links and
pagination.

## Installation

```bash
composer require happenv-com/filament-comments
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag=happenv-filament-comments-migrations
php artisan migrate
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=happenv-filament-comments-config
```

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
example `internalNotes()`); every relationship must declare its return type (`MorphMany`, `HasMany`, ...).

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

The component is hidden while the schema has no record (e.g. on a create page).

### Configuration

Every setting accepts a value or a closure returning it. Closures are evaluated once, when the list is mounted, with
the usual Filament injections (`$record`, `$livewire`, ...).

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
    ->mentionProviders([UserMentionProvider::class])
    ->canComment(fn (Issue $record): bool => auth()->user()->can('comment', $record));
```

With the form below the list, each page is shown oldest to newest so the conversation reads towards the form.

### Deep links

Every comment has a "copy link" action. Opening a link such as `?comments_comment_id=42` opens the page containing
the comment and highlights it. This works with all three pagination types.

### Authors

The author model defaults to the model of the `users` auth provider. Change it in `config/filament-comments.php`:

```php
'author_model' => App\Models\Admin::class,
```

## Extending

The list is rendered by a nested Livewire component. The component receives its configuration as one validated,
immutable `CommentsSettings` object, stored in a locked Livewire property, so the browser can't change it. For the
same reason, extension points are class names that implement a contract, not closures:

| Setting | Contract | Default |
| --- | --- | --- |
| `saveAction()` | `Contracts\SavesComment` | `Actions\SaveCommentAction` |
| `formSchema()` | `Contracts\ConfiguresCommentForm` | `Filament\Schemas\CommentFormSchema` |
| `itemSchema()` | `Contracts\ConfiguresCommentItem` | `Filament\Schemas\CommentItemSchema` |
| `commentItemComponent()` | extends `Filament\Components\Comment` | `Filament\Components\Comment` |
| `mentionProviders()` | `Contracts\ProvidesMentions` | none |

A custom save action, for example:

```php
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

The default comment component can be customized by extending it and overriding any of the `getDefault*Component()`
methods (header, author, created at, content, footer) or the `getQuoteAction()` / `getShareAction()` methods.

The list dispatches a `comment-added` browser event (with the `relationship` name) after a comment is saved.

## Testing

```bash
composer test
composer analyse
```

The test suite runs on Pest 5 against both Filament 4 (Livewire 3) and Filament 5 (Livewire 4) on Laravel 13.
