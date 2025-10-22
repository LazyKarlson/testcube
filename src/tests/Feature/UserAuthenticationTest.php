<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders to set up roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                ],
                'access_token',
                'token_type',
            ])
            ->assertJson([
                'message' => 'Login successful',
                'user' => [
                    'email' => 'test@example.com',
                ],
                'token_type' => 'Bearer',
            ]);

        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_user_receives_valid_access_token_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $response->json('access_token');

        // Verify token works for authenticated requests
        $authResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user');

        $authResponse->assertStatus(200)
            ->assertJson([
                'user' => [
                    'email' => 'test@example.com',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_email(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ]);
    }

    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_supports_multiple_sessions(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // First login
        $firstResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $firstToken = $firstResponse->json('access_token');

        // Second login
        $secondResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $secondToken = $secondResponse->json('access_token');

        // Both tokens should work (multiple sessions supported)
        $response = $this->withHeader('Authorization', 'Bearer '.$firstToken)
            ->getJson('/api/user');

        $response->assertStatus(200);

        // Second token should also work
        $response = $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->getJson('/api/user');

        $response->assertStatus(200);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Login
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('access_token');

        // Logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        // Token should no longer work
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $user->assignRole('author');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'roles',
                    'permissions',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'roles' => ['author'],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_user_profile_includes_roles_and_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'roles',
                    'permissions',
                ],
            ]);

        $this->assertContains('admin', $response->json('user.roles'));
        $this->assertNotEmpty($response->json('user.permissions'));
    }

    public function test_login_is_case_insensitive_for_email(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }

    public function test_multiple_users_can_be_logged_in_simultaneously(): void
    {
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => Hash::make('password123'),
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Login user 1
        $response1 = $this->postJson('/api/login', [
            'email' => 'user1@example.com',
            'password' => 'password123',
        ]);

        $token1 = $response1->json('access_token');

        // Login user 2
        $response2 = $this->postJson('/api/login', [
            'email' => 'user2@example.com',
            'password' => 'password123',
        ]);

        $token2 = $response2->json('access_token');

        // Clear any cached authentication between requests
        auth()->guard('sanctum')->forgetUser();

        // Test token1
        $authResponse1 = $this->withHeader('Authorization', 'Bearer '.$token1)
            ->getJson('/api/user');

        $authResponse1->assertStatus(200)
            ->assertJson(['user' => ['email' => 'user1@example.com']]);

        // Clear authentication again before testing token2
        auth()->guard('sanctum')->forgetUser();

        // Test token2
        $authResponse2 = $this->withHeader('Authorization', 'Bearer '.$token2)
            ->getJson('/api/user');

        $authResponse2->assertStatus(200)
            ->assertJson(['user' => ['email' => 'user2@example.com']]);
    }

    public function test_invalid_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token_here')
            ->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_malformed_authorization_header_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'InvalidFormat')
            ->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_missing_authorization_header_returns_401(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }
}
