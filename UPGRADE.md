# Upgrade guide

## From 1.x to 2.x

2.x changes how the `Comments` component passes its configuration to the comments list. In 1.x every setting was a
public, unlocked Livewire property, so a user could overwrite them from the browser. For example, a user could
change the relationship name so the list called `$record->delete()`, or swap the save action for any class in the
container. In 2.x the configuration is one validated `CommentsSettings` object stored in a locked property.

### Custom save actions

Save actions must implement `Happenv\FilamentComments\Contracts\SavesComment`. The signature changed:

```php
// 1.x
public function __invoke(Model $record, array $data, Authenticatable $user, string $relationName, string $commentItemContentFieldName): Model

// 2.x
public function __invoke(Model $record, array $data, Authenticatable $author, CommentsSettings $settings): Model
```

Use `$settings->relationship` and `$settings->contentField` in place of the removed arguments.

`saveAction()` accepts a class name, or a closure that returns one. In 1.x, a closure passed there was run while the
page was rendering, not when a comment was saved.

### Custom form and item schemas

Both must implement a contract and receive the settings object:

```php
// 1.x
public static function configure(Schema $schema, array $mentionProviders, string $commentContentFieldName, CommentFormat $commentFormat): Schema

// 2.x (Contracts\ConfiguresCommentForm / Contracts\ConfiguresCommentItem)
public static function configure(Schema $schema, CommentsSettings $settings): Schema
```

`CommentFormSchema` and `CommentItemSchema` are no longer `final`, so you can extend them.

### Mention providers

Mention providers must implement `Happenv\FilamentComments\Contracts\ProvidesMentions`, which only requires a static
`make(): MentionProvider` method. `UserMentionProvider` already implements it.

### `CommentsList` Livewire component

If you mounted or extended `CommentsList` yourself:

- `mount()` now takes `(Model $record, CommentsSettings $settings)`. Build the settings with
  `Comments::make('comments')->...->toSettings()`.
- The individual public properties (`name`, `formLocation`, `paginationType`, `paginationPerPage`, `saveAction`, ...)
  were removed. Read them from `$this->settings`. The relationship name is `$this->settings->relationship`.
- `paginationPerPage` was renamed to `perPage`. It is limited to the configured options.
- The `comments` and `commentsList` computed properties were removed. The view now receives `$comments` (the paginator)
  and `$items` (the comments in display order).
- The `quote-comment` event listener was removed. The quote action calls `$wire.quoteComment(id)` directly.
- The `highlight-comment` and `comment-added` events now carry named parameters: `commentId` and `relationship`.

### `Comment` item component

- The default `Comment::make()` name is now `content`, not `comment`. It is also no longer markdown by default:
  `CommentItemSchema` calls `->markdown()` only for `CommentFormat::Markdown`. In 1.x, HTML comments were also run
  through the markdown parser.
- The parts (`headerComponent()`, `contentComponent()`, ...) now accept closures. They are resolved lazily, so
  configuration applied after `make()` takes effect.
- The `$name` property is now protected. Use `getName()`.

### Validation

Invalid configuration now throws an `InvalidArgumentException` when the list is built:

- the default page size must be one of `paginationOptions()`;
- the relationship name, sort column and content field must be plain identifiers;
- extension classes must implement their contracts;
- the relationship method must declare a relation return type (e.g. `: MorphMany`).

### Authors

`Comment::author()` no longer depends on the logged-in user. The author model comes from the new
`filament-comments.author_model` config option. It falls back to `auth.providers.users.model`.

### Commentable rich content hook

A commentable model's `setUpCommentsRichContent(Comment $comment)` method is now actually called. In 1.x the lookup
checked the wrong model.

### Other changes

- Two `Comments` components on the same page (e.g. `comments` and `internalNotes`) no longer share a Livewire key.
- Cursor pagination works. In 1.x it threw an error.
- Changing the page size, or adding a comment, goes back to the first page.
- Comments are ordered by the sort column and then by primary key, so pages are stable when timestamps are equal.
- An empty state is shown when there are no comments.
- The removed `Comments` facade alias no longer points to a class that doesn't exist.
