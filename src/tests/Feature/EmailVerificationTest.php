<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders to set up roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_authenticated_user_can_request_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/email/verification-notification');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Verification email sent',
            ]);
    }

    public function test_already_verified_user_cannot_request_verification_email(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/email/verification-notification');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Email already verified',
            ]);
    }

    public function test_unauthenticated_user_cannot_request_verification_email(): void
    {
        $response = $this->postJson('/api/email/verification-notification');

        $response->assertStatus(401);
    }

    public function test_user_can_verify_email_with_valid_link(): void
    {
        Event::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email verified successfully',
            ]);

        // Assert user is now verified
        $this->assertNotNull($user->fresh()->email_verified_at);

        // Assert Verified event was fired
        Event::assertDispatched(Verified::class);
    }

    public function test_email_verification_fails_with_invalid_hash(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => 'invalid_hash',
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Invalid verification link',
            ]);

        // Assert user is still not verified
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_email_verification_fails_with_invalid_user_id(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => 99999,
                'hash' => 'some_hash',
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(404);
    }

    public function test_already_verified_email_returns_appropriate_message(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Email already verified',
            ]);
    }

    public function test_authenticated_user_can_check_verification_status(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/email/verification-status');

        $response->assertStatus(200)
            ->assertJson([
                'verified' => false,
                'email' => $user->email,
            ]);
    }

    public function test_verified_user_status_check_returns_true(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/email/verification-status');

        $response->assertStatus(200)
            ->assertJson([
                'verified' => true,
                'email' => $user->email,
            ]);
    }

    public function test_unauthenticated_user_cannot_check_verification_status(): void
    {
        $response = $this->getJson('/api/email/verification-status');

        $response->assertStatus(401);
    }

    public function test_verification_link_expires_after_timeout(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // Create an expired verification URL (signed 61 minutes ago)
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(61),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($verificationUrl);

        // Laravel returns 403 for invalid signature (expired)
        $response->assertStatus(403);

        // Assert user is still not verified
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_user_profile_shows_verification_status(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'email_verified_at',
                ],
            ])
            ->assertJson([
                'user' => [
                    'email_verified_at' => null,
                ],
            ]);
    }

    public function test_verified_user_profile_shows_verification_timestamp(): void
    {
        $verifiedAt = now();
        $user = User::factory()->create(['email_verified_at' => $verifiedAt]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);

        $this->assertNotNull($response->json('user.email_verified_at'));
    }

    public function test_multiple_verification_requests_dont_cause_errors(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        Sanctum::actingAs($user);

        // Send multiple verification requests
        $response1 = $this->postJson('/api/email/verification-notification');
        $response2 = $this->postJson('/api/email/verification-notification');
        $response3 = $this->postJson('/api/email/verification-notification');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);
    }

    public function test_verification_works_for_different_users(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com', 'email_verified_at' => null]);
        $user2 = User::factory()->create(['email' => 'user2@example.com', 'email_verified_at' => null]);

        // Verify user1
        $verificationUrl1 = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user1->id,
                'hash' => sha1($user1->email),
            ]
        );

        $response1 = $this->getJson($verificationUrl1);
        $response1->assertStatus(200);

        // Verify user2
        $verificationUrl2 = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user2->id,
                'hash' => sha1($user2->email),
            ]
        );

        $response2 = $this->getJson($verificationUrl2);
        $response2->assertStatus(200);

        // Both users should be verified
        $this->assertNotNull($user1->fresh()->email_verified_at);
        $this->assertNotNull($user2->fresh()->email_verified_at);
    }

    public function test_verification_hash_is_user_specific(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com', 'email_verified_at' => null]);
        $user2 = User::factory()->create(['email' => 'user2@example.com', 'email_verified_at' => null]);

        // Try to verify user2 with user1's hash
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user2->id,
                'hash' => sha1($user1->email), // Wrong hash
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(403);

        // User2 should not be verified
        $this->assertNull($user2->fresh()->email_verified_at);
    }
}
