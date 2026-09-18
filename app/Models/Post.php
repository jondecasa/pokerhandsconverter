<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body',
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

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('published_at', '<=', now());
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
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
