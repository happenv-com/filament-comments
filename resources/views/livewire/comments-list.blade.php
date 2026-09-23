@use ('Happenv\FilamentComments\Enums\CommentFormLocation')
@use ('Happenv\FilamentComments\Enums\CommentsPaginationLocation')
@use ('Illuminate\Support\Js')

@php
    $relationship = $this->settings->relationship;
    $showForm = $this->settings->canComment;
    $paginationAbove = in_array($this->settings->paginationLocation, [CommentsPaginationLocation::Above, CommentsPaginationLocation::Both], true);
    $paginationBelow = in_array($this->settings->paginationLocation, [CommentsPaginationLocation::Below, CommentsPaginationLocation::Both], true);
    $hasPages = $items->isNotEmpty();
@endphp

<div
    class="grid gap-6"
    x-data
    x-on:highlight-comment.window="
        if ($event.detail.relationship !== {{ Js::from($relationship) }}) return;
        const element = document.getElementById({{ Js::from('comment-'.$relationship.'-') }} + $event.detail.commentId);
        if (! element) return;
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('ring-2', 'ring-primary-500', 'rounded-xl');
        setTimeout(() => element.classList.remove('ring-2', 'ring-primary-500', 'rounded-xl'), 3000);
    "
>
    @if ($showForm && $this->settings->formLocation === CommentFormLocation::Above)
        <div class="fm-add-comment" id="comment-form-{{ $relationship }}">
            {{ $this->form }}
        </div>
    @endif

    @if ($paginationAbove && $hasPages)
        <x-filament::pagination
            current-page-option-property="perPage"
            :extreme-links="true"
            :page-options="$this->settings->perPageOptions"
            :paginator="$comments"
        />
    @endif

    @forelse ($items as $comment)
        <div
            wire:key="comment-{{ $relationship }}-{{ $comment->getKey() }}"
            id="comment-{{ $relationship }}-{{ $comment->getKey() }}"
            class="fm-comment-item"
        >
            {{ $this->commentItem($comment) }}
        </div>
    @empty
        <div class="fm-comments-empty text-sm text-gray-500 dark:text-gray-400">
            {{ __('happenv-filament-comments::comments.no_comments') }}
        </div>
    @endforelse

    @if ($paginationBelow && $hasPages)
        <x-filament::pagination
            current-page-option-property="perPage"
            :extreme-links="true"
            :page-options="$this->settings->perPageOptions"
            :paginator="$comments"
        />
    @endif

    @if ($showForm && $this->settings->formLocation === CommentFormLocation::Below)
        <div class="fm-add-comment" id="comment-form-{{ $relationship }}">
            {{ $this->form }}
        </div>
    @endif
</div>
