<?php

namespace App\Observers;

use App\Models\Post;
use App\Support\IndexNow;

class PostObserver
{
    public function __construct(private IndexNow $indexNow) {}

    /** A post that is live now, or was live before this save (unpublished, edited, renamed). */
    public function saved(Post $post): void
    {
        if ($post->isPublished() || $this->wasPublic($post)) {
            $this->notify($post);
        }
    }

    public function deleted(Post $post): void
    {
        if ($post->isPublished()) {
            $this->notify($post);
        }
    }

    private function wasPublic(Post $post): bool
    {
        return (bool) $post->getOriginal('is_published') && $post->getOriginal('published_at')?->isPast();
    }

    private function notify(Post $post): void
    {
        $urls = [route('blog.show', $post), route('blog.index')];

        $previousSlug = $post->getOriginal('slug');
        if ($previousSlug && $previousSlug !== $post->slug) {
            $urls[] = route('blog.show', $previousSlug);
        }

        $this->indexNow->submit($urls);
    }
}
