<?php

namespace Tests\Feature;

use App\Models\RangeScenario;
use App\Models\RangeStudy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminRangeStudyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);
    }

    #[Test]
    public function guests_are_sent_to_login_from_the_admin_range_area(): void
    {
        $this->get('/admin/range-studies')->assertRedirect('/login');
    }

    #[Test]
    public function logged_in_non_admins_get_403_from_the_admin_range_area(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin/range-studies')->assertForbidden();
        $this->actingAs($user)->post('/admin/range-studies', [])->assertForbidden();
    }

    #[Test]
    public function an_admin_can_see_the_studies_list(): void
    {
        RangeStudy::factory()->create(['name' => '6MAX 100BB']);

        $this->actingAs($this->admin())->get('/admin/range-studies')
            ->assertOk()
            ->assertSee('6MAX 100BB');
    }

    #[Test]
    public function an_admin_can_create_a_study(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/range-studies', [
            'name' => '6MAX 100BB',
            'slug' => '6max-100bb',
            'sort_order' => '1',
        ]);

        $study = RangeStudy::where('slug', '6max-100bb')->firstOrFail();
        $response->assertRedirect(route('admin.range-studies.edit', $study));
        $this->assertSame('6MAX 100BB', $study->name);
    }

    #[Test]
    public function an_admin_can_update_and_delete_a_study(): void
    {
        $study = RangeStudy::factory()->create(['name' => 'Old name']);

        $this->actingAs($this->admin())->put("/admin/range-studies/{$study->slug}", [
            'name' => 'New name',
            'slug' => $study->slug,
            'sort_order' => '0',
        ])->assertRedirect(route('admin.range-studies.edit', $study));

        $this->assertSame('New name', $study->fresh()->name);

        $this->actingAs($this->admin())->delete("/admin/range-studies/{$study->slug}")
            ->assertRedirect(route('admin.range-studies.index'));
        $this->assertModelMissing($study);
    }

    #[Test]
    public function an_admin_can_add_a_scenario_with_a_painted_grid_and_stats(): void
    {
        $study = RangeStudy::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('admin.range-studies.scenarios.store', $study), [
            'group_label' => 'Sin oposición',
            'group_order' => '0',
            'row_label' => 'EP',
            'row_order' => '0',
            'button_label' => '100BB',
            'button_order' => '0',
            'is_default' => '1',
            'legend' => [
                ['key' => 'raise', 'label' => 'Raise', 'color' => '#22c55e'],
                ['key' => '', 'label' => '', 'color' => '#000000'], // blank row should be dropped
            ],
            'combos' => [
                'AA' => 'raise',
                'KK' => 'raise',
                '72o' => '', // empty selection should be dropped, not stored as ""
            ],
            'stats' => [
                'badges' => [
                    ['label' => 'VPIP', 'value' => '16.9%', 'highlight' => '0'],
                    ['label' => '', 'value' => '', 'highlight' => '0'], // blank row dropped
                ],
                'bars' => [
                    ['label' => 'F', 'pct' => '65', 'color' => '#ef4444'],
                ],
            ],
        ]);

        $scenario = $study->scenarios()->firstOrFail();
        $response->assertRedirect(route('admin.range-studies.edit', $study));

        $this->assertTrue($scenario->is_default);
        $this->assertSame([['key' => 'raise', 'label' => 'Raise', 'color' => '#22c55e']], $scenario->legend);
        $this->assertSame(['AA' => 'raise', 'KK' => 'raise'], $scenario->combos);
        $this->assertSame('16.9%', $scenario->stats['badges'][0]['value']);
        $this->assertCount(1, $scenario->stats['badges']);
        $this->assertSame(65, $scenario->stats['bars'][0]['pct']);
    }

    #[Test]
    public function an_admin_can_update_and_delete_a_scenario(): void
    {
        $study = RangeStudy::factory()->create();
        $scenario = RangeScenario::factory()->for($study, 'study')->create(['button_label' => '100BB']);

        $this->actingAs($this->admin())->put(route('admin.range-scenarios.update', $scenario), [
            'group_label' => $scenario->group_label,
            'row_label' => $scenario->row_label,
            'button_label' => '150BB',
            'legend' => $scenario->legend,
            'combos' => $scenario->combos,
        ])->assertRedirect(route('admin.range-studies.edit', $study));

        $this->assertSame('150BB', $scenario->fresh()->button_label);

        $this->actingAs($this->admin())->delete(route('admin.range-scenarios.destroy', $scenario))
            ->assertRedirect(route('admin.range-studies.edit', $study));
        $this->assertModelMissing($scenario);
    }
}
