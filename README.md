# Filament Comments Module

Polymorphic comments module for Filamnet. Provides reusable Livewire components for adding and displaying comments on any model.

## Installation

```
composer require happenv-com/filament-comments
```

## Usage

### Making a model commentable

Add the `HasComments` trait to any model that should support comments:

```php
use Happenv\FilamentComments\Concerns\HasComments;

class Issue extends Model
{
    use HasComments;
}
```

### Using in Filament

Add the comments components to your Filament infolist or page:

```php
use Happenv\FilamentComments\Filament\Components\CommentsSection;

// In your infolist schema
CommentsSection::make()
    ->record($record)
```

Or use the individual components:

```php
@livewire('happenv-filament-comments-list', ['commentableType' => 'issues.issue', 'commentableId' => $record->id])
@livewire('happenv-filament-comments-add', ['commentableType' => 'issues.issue', 'commentableId' => $record->id])
```

## Features

- Polymorphic comments (can be attached to any model)
- Rich text content with mentions support
- User attribution (author)
- Soft deletes
- Activity tracking (created_by, updated_by)
