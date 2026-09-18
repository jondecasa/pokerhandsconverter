<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * The initial blog content — a cross-linked cluster mixing CoinPoker/tracker
 * guides with general poker strategy (rake, bankroll, HUD stats). Fixture
 * lives in seeders/data/ since the post bodies are long-form Markdown.
 */
class PostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = json_decode(file_get_contents(__DIR__.'/data/posts.json'), true);

        foreach ($posts as $index => $data) {
            Post::updateOrCreate(
                ['slug' => $data['slug'], 'locale' => $data['locale'] ?? 'en'],
                [
                    'translation_of' => $data['translation_of'] ?? null,
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'body' => $data['body'],
                    'meta_title' => $data['meta_title'],
                    'meta_description' => $data['meta_description'],
                    'is_published' => true,
                    // Staggered, oldest first (matches the JSON's order), so
                    // the blog index doesn't show ten posts published in the
                    // same second the first time this runs.
                    'published_at' => now()->subMinutes((count($posts) - $index) * 5),
                ]
            );
        }
    }
}
