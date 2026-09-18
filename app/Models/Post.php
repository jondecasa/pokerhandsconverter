<?php

namespace App\Models;

use App\Observers\PostObserver;
use App\Support\Locales;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[ObservedBy(PostObserver::class)]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'locale', 'translation_of', 'excerpt', 'body',
        'meta_title', 'meta_description',
        'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Public URLs use the slug within the language of the page being viewed
     * (the same slug can exist in several languages); anything bound by
     * another field, like the admin's {post:id}, is looked up as is.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();
        $query = $this->newQuery()->where($field, $value);

        if ($field === 'slug') {
            $query->where('locale', app()->getLocale());
        }

        return $query->first();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('published_at', '<=', now());
    }

    public function scopeInLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
    }

    public function url(): string
    {
        return Locales::route('blog.show', ['post' => $this->slug], $this->locale);
    }

    /** Slug of the default-language post this one and its translations share. */
    private function originalSlug(): ?string
    {
        return Locales::isDefault($this->locale) ? $this->slug : $this->translation_of;
    }

    /**
     * The published versions of this post in the other languages.
     *
     * @return Collection<int, Post>
     */
    public function translations(): Collection
    {
        $original = $this->originalSlug();

        if (! $original) {
            return new Collection;
        }

        return static::published()
            ->where('id', '!=', $this->id)
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->where('locale', Locales::default())->where('slug', $original))
                ->orWhere('translation_of', $original))
            ->get();
    }

    /**
     * URL of this post in every language it exists in (itself included), for
     * hreflang. Empty when it has no translations.
     *
     * @return array<string, string>
     */
    public function alternates(): array
    {
        $translations = $this->translations();

        if ($translations->isEmpty()) {
            return [];
        }

        return $translations->push($this)->mapWithKeys(fn (Post $post) => [$post->locale => $post->url()])->all();
    }

    public function isPublished(): bool
    {
        return $this->is_published && $this->published_at?->isPast();
    }

    public function bodyHtml(): string
    {
        return Str::markdown((string) $this->body, ['html_input' => 'strip']);
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: $this->title.' — PokerHandsConverter';
    }

    public function metaDescription(): string
    {
        return $this->meta_description ?: ($this->excerpt ?: Str::limit(trim(strip_tags($this->bodyHtml())), 155));
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->bodyHtml())) / 200));
    }
}
