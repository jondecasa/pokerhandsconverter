<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeFlopzerSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_home_page_presents_flopzer_as_included_with_every_plan(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Flopzer: see how your range hits the board')
            ->assertSee('Included with every plan')
            ->assertSee('lives in your browser')
            ->assertSee('Flopzilla is a trademark of its owner')
            ->assertSee(route('register'), false);
    }

    #[Test]
    public function a_signed_in_visitor_gets_a_link_straight_to_the_tool(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
            ->get('/')->assertOk()->assertSee(route('flopzer.index'), false);
    }

    #[Test]
    public function the_chinese_home_page_is_translated(): void
    {
        $this->get('/zh-hant')->assertOk()
            ->assertSee('Flopzer：看看你的範圍如何打中公共牌')
            ->assertSee('所有方案皆包含')
            ->assertDontSee('Flopzer: see how your range hits the board');
    }
}
