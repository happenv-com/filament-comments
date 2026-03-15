@use ('Happenv\Comments\Enums\CommentFormLocation')
@use ('Happenv\Comments\Enums\CommentsPaginationLocation')

<div
    class="grid gap-6"
    x-data
    @highlight-comment.window="
        const commentId = $event.detail[0];
        console.log('comment-{{ $this->name }}-' + commentId);
        const element = document.getElementById('comment-{{ $this->name }}-' + commentId);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            element.classList.add('ring-2', 'ring-primary-500', 'rounded-xl');
            setTimeout(() => element.classList.remove('ring-2', 'ring-primary-500', 'rounded-lg'), 3000);
        }
    "
>
    @if ($this->formLocation === CommentFormLocation::Above)
        <div class="fm-add-comment" id="comment-form-{{ $this->name }}">
            {{ $this->form }}
        </div>
    @endif

    @if ($this->paginationLocation === CommentsPaginationLocation::Above ||
    $this->paginationLocation === CommentsPaginationLocation::Both)
        <x-filament::pagination
            :extreme-links="true"
            :page-options="$this->paginationOptions"
            :paginator="$this->comments"
        />
    @endif

    @foreach ($this->commentsList as $comment)
        <div
            id="comment-{{ $this->name }}-{{ $comment->id }}"
            class="fm-comment-item"
        >
            {{ $this->commentItem($comment) }}
        </div>
    @endforeach

    @if ($this->paginationLocation === CommentsPaginationLocation::Below ||
    $this->paginationLocation === CommentsPaginationLocation::Both)
        <x-filament::pagination
            :current-page-option-property="'paginationPerPage'"
            :extreme-links="true"
            :page-options="$this->paginationOptions"
            :paginator="$this->comments"
        />
    @endif

    @if ($this->formLocation === CommentFormLocation::Below)
        <div class="fm-add-comment" id="comment-form-{{ $this->name }}">
            {{ $this->form }}
        </div>
    @endif
</div>
