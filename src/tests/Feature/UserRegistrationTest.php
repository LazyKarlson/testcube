<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders to set up roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_user_can_register_with_valid_data(): void
    {
        Event::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'roles',
                    'created_at',
                ],
                'access_token',
                'token_type',
            ])
            ->assertJson([
                'message' => 'User registered successfully. Please check your email to verify your account.',
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                'token_type' => 'Bearer',
            ]);

        // Assert user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Assert password was hashed
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));

        // Assert Registered event was fired
        Event::assertDispatched(Registered::class);
    }

    public function test_registered_user_gets_author_role_by_default(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertTrue($user->hasRole('author'));
        $this->assertContains('author', $response->json('user.roles'));
    }

    public function test_registered_user_receives_access_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'access_token',
                'token_type',
            ]);

        $token = $response->json('access_token');
        $this->assertNotEmpty($token);

        // Verify token works for authenticated requests
        $authResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user');

        $authResponse->assertStatus(200);
    }

    public function test_registration_requires_name(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_registration_requires_email(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_unique_email(): void
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_password_minimum_8_characters(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_limits_name_to_255_characters(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => str_repeat('a', 256),
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_registration_limits_email_to_255_characters(): void
    {
        // Create an email longer than 255 characters (244 + @ + example.com = 256)
        $longEmail = str_repeat('a', 244).'@example.com';

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => $longEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_email_is_case_insensitive(): void
    {
        // Create user with lowercase email
        User::factory()->create(['email' => 'test@example.com']);

        // Try to register with uppercase email
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_trims_whitespace_from_inputs(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '  John Doe  ',
            'email' => '  john@example.com  ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
    }

    public function test_multiple_users_can_register_successfully(): void
    {
        $users = [
            ['name' => 'User 1', 'email' => 'user1@example.com'],
            ['name' => 'User 2', 'email' => 'user2@example.com'],
            ['name' => 'User 3', 'email' => 'user3@example.com'],
        ];

        foreach ($users as $userData) {
            $response = $this->postJson('/api/register', [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertStatus(201);
        }

        $this->assertEquals(3, User::count());
    }

    public function test_registered_user_email_is_not_verified_initially(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'user' => [
                    'email_verified_at' => null,
                ],
            ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNull($user->email_verified_at);
    }
}
