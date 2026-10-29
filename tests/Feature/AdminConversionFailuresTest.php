<?php

namespace Tests\Feature;

use App\Models\ConversionFailure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminConversionFailuresTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);
    }

    #[Test]
    public function guests_are_sent_to_login_and_non_admins_get_403(): void
    {
        $this->get('/admin/failures')->assertRedirect('/login');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user)->get('/admin/failures')->assertForbidden();
        $this->actingAs($user)->delete('/admin/failures?all=1')->assertForbidden();

        $failure = ConversionFailure::factory()->create();
        $this->actingAs($user)->delete('/admin/failures/'.$failure->id)->assertForbidden();
        $this->assertSame(1, ConversionFailure::count());
    }

    #[Test]
    public function an_admin_sees_every_logged_failure_with_user_file_reason_and_details(): void
    {
        $owner = User::factory()->create(['name' => 'Fiona Fail', 'email' => 'fiona@example.com']);
        ConversionFailure::factory()->create([
            'user_id' => $owner->id,
            'original_filename' => 'broken-session.txt',
            'file_size' => 2048,
            'reason' => 'binary',
            'message' => 'That looks like a binary file, not a text hand history.',
            'details' => 'Starts with a UTF-16 byte-order mark',
            'ip' => '203.0.113.7',
        ]);

        $this->actingAs($this->admin())->get('/admin/failures')
            ->assertOk()
            ->assertSee('Conversion logs')
            ->assertSee('Fiona Fail')
            ->assertSee('fiona@example.com')
            ->assertSee('broken-session.txt')
            ->assertSee('2 KB')
            ->assertSee('Binary / wrong encoding')
            ->assertSee('That looks like a binary file')
            ->assertSee('Starts with a UTF-16 byte-order mark')
            ->assertSee('203.0.113.7');
    }

    #[Test]
    public function the_empty_state_is_explained(): void
    {
        $this->actingAs($this->admin())->get('/admin/failures')
            ->assertOk()
            ->assertSee('Nothing logged yet');
    }

    #[Test]
    public function entries_can_be_filtered_by_reason_and_searched(): void
    {
        ConversionFailure::factory()->create(['original_filename' => 'alpha-file.txt', 'reason' => 'empty', 'message' => 'That file looks empty.']);
        ConversionFailure::factory()->create(['original_filename' => 'beta-file.txt', 'reason' => 'exception', 'message' => 'Something went wrong']);
        $mia = User::factory()->create(['email' => 'mia@example.com']);
        ConversionFailure::factory()->create(['user_id' => $mia->id, 'original_filename' => 'gamma-file.txt']);

        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/failures?reason=exception')
            ->assertOk()->assertSee('beta-file.txt')->assertDontSee('alpha-file.txt')->assertDontSee('gamma-file.txt');

        $this->actingAs($admin)->get('/admin/failures?q=alpha')
            ->assertOk()->assertSee('alpha-file.txt')->assertDontSee('beta-file.txt');

        $this->actingAs($admin)->get('/admin/failures?q=mia@example.com')
            ->assertOk()->assertSee('gamma-file.txt')->assertDontSee('alpha-file.txt');

        $this->actingAs($admin)->get('/admin/failures?q=nothing-matches-this')
            ->assertOk()->assertSee('No entries match your filters');
    }

    #[Test]
    public function an_entry_can_be_deleted_on_its_own(): void
    {
        [$keep, $drop] = [ConversionFailure::factory()->create(), ConversionFailure::factory()->create()];

        $this->actingAs($this->admin())->delete('/admin/failures/'.$drop->id)
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('conversion_failures', ['id' => $keep->id]);
        $this->assertDatabaseMissing('conversion_failures', ['id' => $drop->id]);
    }

    #[Test]
    public function several_entries_can_be_deleted_at_once(): void
    {
        $failures = ConversionFailure::factory()->count(4)->create();
        $ids = $failures->take(3)->pluck('id')->all();

        $this->actingAs($this->admin())->delete('/admin/failures?'.http_build_query(['ids' => $ids]))
            ->assertRedirect('/admin/failures')
            ->assertSessionHas('status', 'Deleted 3 log entries.');

        $this->assertSame([$failures->last()->id], ConversionFailure::pluck('id')->all());
    }

    #[Test]
    public function deleting_with_nothing_selected_does_nothing_and_says_so(): void
    {
        ConversionFailure::factory()->count(2)->create();

        $this->actingAs($this->admin())->delete('/admin/failures')
            ->assertRedirect('/admin/failures')
            ->assertSessionHas('error');

        $this->assertSame(2, ConversionFailure::count());
    }

    #[Test]
    public function delete_all_removes_everything_that_matches_the_current_filter_only(): void
    {
        ConversionFailure::factory()->count(3)->create(['reason' => 'empty']);
        ConversionFailure::factory()->count(2)->create(['reason' => 'exception']);

        $admin = $this->admin();

        $this->actingAs($admin)->delete('/admin/failures?all=1&reason=empty')
            ->assertRedirect('/admin/failures?reason=empty')
            ->assertSessionHas('status', 'Deleted 3 log entries.');
        $this->assertSame(2, ConversionFailure::where('reason', 'exception')->count());
        $this->assertSame(2, ConversionFailure::count());

        $this->actingAs($admin)->delete('/admin/failures?all=1')->assertSessionHas('status', 'Deleted 2 log entries.');
        $this->assertSame(0, ConversionFailure::count());
    }

    #[Test]
    public function the_nav_links_to_the_log_for_admins_only(): void
    {
        $this->actingAs($this->admin())->get('/dashboard')->assertSee('Conversion logs');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user)->get('/dashboard')->assertDontSee('Conversion logs');
    }
}
