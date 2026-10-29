<?php

namespace Tests\Feature;

use App\Models\ConversionFailure;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversionFailureLogTest extends TestCase
{
    use RefreshDatabase;

    private function subscribedUser(?string $stakesCap = null): User
    {
        $plan = Plan::factory()->create(['slug' => 'test-plan', 'stripe_price_id' => 'price_test', 'stakes_cap' => $stakesCap]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $sub = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
        ]);
        $sub->items()->create([
            'stripe_id' => 'si_'.uniqid(),
            'stripe_product' => 'prod_test',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
        ]);

        return $user;
    }

    private function upload(string $name, string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    #[Test]
    public function an_empty_file_is_logged(): void
    {
        $user = $this->subscribedUser();

        $this->actingAs($user)->post('/convert', ['file' => $this->upload('empty.txt', '')])
            ->assertSessionHasErrors('file');

        $failure = ConversionFailure::sole();
        $this->assertSame('empty', $failure->reason);
        $this->assertSame($user->id, $failure->user_id);
        $this->assertSame('empty.txt', $failure->original_filename);
        $this->assertSame(0, $failure->file_size);
        $this->assertSame('That file looks empty.', $failure->message);
    }

    #[Test]
    public function a_utf16_file_is_logged_as_binary_with_a_hint(): void
    {
        $user = $this->subscribedUser();
        $utf16 = "\xFF\xFE".mb_convert_encoding("Hand #1\n", 'UTF-16LE', 'UTF-8');

        $this->actingAs($user)->post('/convert', ['file' => $this->upload('utf16.txt', $utf16)])
            ->assertSessionHasErrors('file');

        $failure = ConversionFailure::sole();
        $this->assertSame('binary', $failure->reason);
        $this->assertStringContainsString('UTF-16', $failure->details);
    }

    #[Test]
    public function a_file_that_is_not_a_coinpoker_history_is_logged_with_its_first_line(): void
    {
        $user = $this->subscribedUser();

        $this->actingAs($user)->post('/convert', ['file' => $this->upload('notes.txt', "\nPokerStars Hand history\nsomething\n")])
            ->assertSessionHasErrors('file');

        $failure = ConversionFailure::sole();
        $this->assertSame('not_coinpoker', $failure->reason);
        $this->assertStringContainsString('First line: PokerStars Hand history', $failure->details);
    }

    #[Test]
    public function a_file_with_no_parsable_hands_is_logged(): void
    {
        $user = $this->subscribedUser();

        $this->actingAs($user)->post('/convert', ['file' => $this->upload('odd.txt', "Hand #1 is mentioned here but nothing else\n")])
            ->assertSessionHasErrors('file');

        $failure = ConversionFailure::sole();
        $this->assertSame('no_hands', $failure->reason, (string) $failure->details);
        $this->assertSame('No hands could be parsed from that file.', $failure->message);
        $this->assertStringContainsString('First line: Hand #1 is mentioned here', $failure->details);
    }

    #[Test]
    public function exceeding_the_stakes_cap_is_logged(): void
    {
        Storage::fake('local');
        $user = $this->subscribedUser('NL2');

        $this->actingAs($user)->post('/convert', ['file' => $this->upload('nl50.txt', file_get_contents(base_path('samples/coinpoker-nl50-example.txt')))])
            ->assertSessionHasErrors('file');

        $failure = ConversionFailure::sole();
        $this->assertSame('stakes_cap', $failure->reason);
        $this->assertStringContainsString('NL2', $failure->details);
    }

    #[Test]
    public function a_file_over_the_size_limit_is_logged_with_the_server_limits(): void
    {
        config(['pokerhandsconverter.max_upload_kb' => 1]);
        $user = $this->subscribedUser();

        $this->actingAs($user)->post('/convert', ['file' => UploadedFile::fake()->create('big.txt', 5)])
            ->assertSessionHasErrors('file');

        $failure = ConversionFailure::sole();
        $this->assertSame('upload', $failure->reason);
        $this->assertSame('big.txt', $failure->original_filename);
        $this->assertStringContainsString('post_max_size', $failure->details);
        $this->assertStringContainsString('App limit: 1 KB', $failure->details);
    }

    #[Test]
    public function a_post_without_a_file_is_logged_as_an_upload_problem(): void
    {
        $user = $this->subscribedUser();

        $this->actingAs($user)->post('/convert', [])->assertSessionHasErrors('file');

        $failure = ConversionFailure::sole();
        $this->assertSame('upload', $failure->reason);
        $this->assertNull($failure->original_filename);
        $this->assertStringContainsString('No file reached the app.', $failure->details);
    }

    #[Test]
    public function a_post_over_php_post_max_size_is_logged_with_its_user_and_shows_a_413_page(): void
    {
        $user = $this->subscribedUser();
        DB::table('sessions')->insert([
            'id' => 'session-of-the-reporter', 'user_id' => $user->id, 'ip_address' => '203.0.113.9',
            'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
        ]);

        // The browser sends the session cookie encrypted, with Laravel's name/key prefix in front of the id.
        $name = config('session.cookie');
        $cookie = app('encrypter')->encrypt(CookieValuePrefix::create($name, app('encrypter')->getKey()).'session-of-the-reporter', false);

        // PHP drops the body of a request over post_max_size; Laravel only sees the declared Content-Length.
        $this->call('POST', '/convert', [], [$name => $cookie], [], ['CONTENT_LENGTH' => 10_000_000_000])
            ->assertStatus(413)
            ->assertSee('File too large')
            ->assertSee('Split the hand history');

        $failure = ConversionFailure::sole();
        $this->assertSame('upload', $failure->reason);
        $this->assertSame($user->id, $failure->user_id);
        $this->assertSame(10_000_000_000, $failure->file_size);
        $this->assertStringContainsString('post_max_size', $failure->details);
    }

    #[Test]
    public function an_oversized_post_without_a_known_session_is_still_logged(): void
    {
        $this->call('POST', '/convert', [], [], [], ['CONTENT_LENGTH' => 10_000_000_000])->assertStatus(413);

        $this->assertNull(ConversionFailure::sole()->user_id);
    }

    #[Test]
    public function the_upload_form_states_the_limit_the_server_really_applies(): void
    {
        config(['pokerhandsconverter.max_upload_kb' => 2048]);

        $this->actingAs($this->subscribedUser())->get('/convert')
            ->assertOk()
            ->assertSee('Max 2 MB per file.');
    }

    #[Test]
    public function a_crash_while_converting_is_logged_and_the_user_gets_a_message_instead_of_a_500(): void
    {
        $user = $this->subscribedUser();
        $content = file_get_contents(base_path('tests/Fixtures/coinpoker-cash.txt'));

        // The converted file cannot be written: any exception after parsing takes the same path.
        Storage::shouldReceive('disk')->andThrow(new \RuntimeException('disk full'));

        $this->actingAs($user)->post('/convert', ['file' => $this->upload('cash.txt', $content)])
            ->assertSessionHasErrors('file');

        $failure = ConversionFailure::sole();
        $this->assertSame('exception', $failure->reason);
        $this->assertStringContainsString('RuntimeException: disk full', $failure->details);
        $this->assertStringContainsString('Something went wrong', $failure->message);
        $this->assertSame(0, $user->conversions()->count());
    }

    #[Test]
    public function a_successful_conversion_is_not_logged_as_a_failure(): void
    {
        Storage::fake('local');
        $user = $this->subscribedUser();

        $this->actingAs($user)->post('/convert', ['file' => $this->upload('cash.txt', file_get_contents(base_path('tests/Fixtures/coinpoker-cash.txt')))]);

        $this->assertSame(1, $user->conversions()->count());
        $this->assertSame(0, ConversionFailure::count());
    }

    #[Test]
    public function deleting_a_user_keeps_their_log_entries_without_an_owner(): void
    {
        $user = $this->subscribedUser();
        ConversionFailure::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertNull(ConversionFailure::sole()->user_id);
    }
}
