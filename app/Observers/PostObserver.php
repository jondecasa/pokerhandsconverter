<?php

namespace App\Observers;

use App\Models\Post;
use App\Support\BaiduPush;
use App\Support\IndexNow;
use App\Support\Locales;

class PostObserver
{
    public function __construct(private IndexNow $indexNow, private BaiduPush $baidu) {}

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
        $urls = [$post->url(), Locales::route('blog.index', [], $post->locale)];

        $previousSlug = $post->getOriginal('slug');
        if ($previousSlug && $previousSlug !== $post->slug) {
            $urls[] = Locales::route('blog.show', ['post' => $previousSlug], $post->locale);
        }

        $this->indexNow->submit($urls);

        // Baidu has a small daily quota and mostly matters for Chinese pages.
        if (! Locales::isDefault($post->locale)) {
            $this->baidu->submit($urls);
        }
    }
}
