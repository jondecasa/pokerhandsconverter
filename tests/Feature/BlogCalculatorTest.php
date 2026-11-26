<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlogCalculatorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function each_calculator_shortcode_renders_its_widget_inside_the_post(): void
    {
        $widgets = ['ev' => 'evCalculator', 'winrate' => 'winrateCalculator', 'variance' => 'varianceCalculator'];

        foreach ($widgets as $name => $component) {
            $post = Post::factory()->published()->create([
                'slug' => "calc-$name",
                'body' => "## Intro\n\nSome text.\n\n[calculator:$name]\n\n## How it works\n\nMore text.",
            ]);

            $this->get($post->url())
                ->assertOk()
                ->assertSee('x-data="'.$component.'"', false)
                ->assertSee('data-calculator="'.$name.'"', false)
                ->assertDontSee('[calculator:'.$name.']', false);
        }
    }

    #[Test]
    public function unknown_shortcodes_and_inline_mentions_are_left_as_text(): void
    {
        $post = Post::factory()->published()->create([
            'body' => "[calculator:nope]\n\nType `[calculator:ev]` on its own line, or write [calculator:ev] inside a sentence.",
        ]);

        $html = $this->get($post->url())->assertOk()->getContent();

        $this->assertStringContainsString('[calculator:nope]', $html);
        $this->assertStringNotContainsString('data-calculator', $html);
    }

    #[Test]
    public function raw_html_in_a_body_is_still_stripped(): void
    {
        $post = Post::factory()->published()->create([
            'body' => "<script>alert(1)</script>\n\n<div x-data=\"evCalculator\" data-calculator></div>\n\nText.",
        ]);

        $this->get($post->url())->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('data-calculator', false);
    }

    #[Test]
    public function the_calculator_labels_are_translated_on_chinese_posts(): void
    {
        $post = Post::factory()->published()->create([
            'locale' => 'zh-Hant',
            'slug' => 'calc-zh',
            'body' => '[calculator:variance]',
        ]);

        $this->get($post->url())->assertOk()
            ->assertSee('撲克變異數計算器')
            ->assertSee('破產風險');
    }

    #[Test]
    public function the_widget_markup_does_not_count_towards_reading_time_or_the_excerpt(): void
    {
        $post = Post::factory()->make(['body' => "[calculator:variance]\n\nShort text.", 'excerpt' => null, 'meta_description' => null]);

        $this->assertSame(1, $post->readingMinutes());
        $this->assertStringNotContainsString('Simulate', $post->metaDescription());
    }
}
