<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email, string $name = 'Jon Doe', bool $verified = true): SocialiteUser
    {
        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = $id;
        $socialiteUser->name = $name;
        $socialiteUser->nickname = $name;
        $socialiteUser->email = $email;
        $socialiteUser->user = ['email_verified' => $verified];

        return $socialiteUser;
    }

    #[Test]
    public function the_redirect_route_sends_the_visitor_to_google(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    #[Test]
    public function a_new_visitor_gets_an_account_created_and_is_logged_in(): void
    {
        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser('g-1', 'newuser@example.com', 'New User'));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'newuser@example.com')->firstOrFail();
        $this->assertSame('g-1', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('New User', $user->name);
    }

    #[Test]
    public function an_existing_account_is_linked_and_logged_in_by_matching_email(): void
    {
        $existing = User::factory()->create(['email' => 'already@example.com', 'google_id' => null]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser('g-2', 'already@example.com'));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($existing->fresh());
        $this->assertSame('g-2', $existing->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'already@example.com')->count());
    }

    #[Test]
    public function an_unverified_google_email_is_rejected(): void
    {
        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser('g-3', 'sketchy@example.com', verified: false));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'sketchy@example.com']);
    }
}
