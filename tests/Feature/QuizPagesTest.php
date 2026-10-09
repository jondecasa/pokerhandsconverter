<?php

namespace Tests\Feature;

use App\Models\RangeScenario;
use App\Models\RangeStudy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuizPagesTest extends TestCase
{
    use RefreshDatabase;

    private function subscribedUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'stripe_id' => 'si_'.uniqid(),
            'stripe_product' => 'prod_test',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);

        return $user;
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $study = RangeStudy::factory()->create();

        $this->get('/quiz')->assertRedirect('/login');
        $this->get(route('quiz.show', $study))->assertRedirect('/login');
    }

    #[Test]
    public function non_subscribed_users_are_sent_to_the_plan_picker(): void
    {
        $study = RangeStudy::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/quiz')->assertRedirect(route('subscription.plans'));
        $this->actingAs($user)->get(route('quiz.show', $study))->assertRedirect(route('subscription.plans'));
    }

    #[Test]
    public function the_index_lists_studies_that_have_quizzable_scenarios(): void
    {
        $ready = RangeStudy::factory()->create(['name' => 'Cash 6MAX']);
        RangeScenario::factory()->for($ready, 'study')->create();
        RangeStudy::factory()->create(['name' => 'Empty study']);

        $this->actingAs($this->subscribedUser())->get('/quiz')
            ->assertOk()
            ->assertSee('Quiz Ranges')
            ->assertSee('Cash 6MAX')
            ->assertSee(route('quiz.show', $ready), false)
            ->assertSee('Empty study')
            ->assertSee('Not available for the quiz yet.');
    }

    #[Test]
    public function the_quiz_page_embeds_twenty_questions(): void
    {
        $study = RangeStudy::factory()->create(['name' => 'Cash 6MAX']);
        RangeScenario::factory()->for($study, 'study')->create();

        $this->actingAs($this->subscribedUser())->get(route('quiz.show', $study))
            ->assertOk()
            ->assertSee('Quiz — Cash 6MAX', false)
            ->assertViewHas('questions', fn (array $questions) => count($questions) === 20);
    }

    #[Test]
    public function a_study_with_nothing_to_quiz_says_so(): void
    {
        $study = RangeStudy::factory()->create();

        $this->actingAs($this->subscribedUser())->get(route('quiz.show', $study))
            ->assertOk()
            ->assertSee('no scenarios available for the quiz yet');
    }

    #[Test]
    public function the_sidebar_links_to_the_quiz(): void
    {
        $this->actingAs($this->subscribedUser())->get('/ranges')
            ->assertOk()
            ->assertSee('Quiz Ranges')
            ->assertSee(route('quiz.index'), false);
    }
}
