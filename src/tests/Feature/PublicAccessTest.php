<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders to set up roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_unauthenticated_user_can_list_posts(): void
    {
        Post::factory()->count(5)->create();

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'total',
            ]);
    }

    public function test_unauthenticated_user_can_view_single_post(): void
    {
        $post = Post::factory()->create(['title' => 'Public Post']);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Public Post']);
    }

    public function test_unauthenticated_user_can_search_posts(): void
    {
        Post::factory()->create(['title' => 'Laravel Tutorial']);
        Post::factory()->create(['title' => 'PHP Guide']);

        $response = $this->getJson('/api/posts/search?q=laravel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'query',
                'filters',
                'results',
            ]);
    }

    public function test_unauthenticated_user_can_list_comments(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->count(3)->forPost($post)->create();

        $response = $this->getJson("/api/posts/{$post->id}/comments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'total',
            ]);
    }

    public function test_unauthenticated_user_can_view_single_comment(): void
    {
        $comment = Comment::factory()->create(['body' => 'Public Comment']);

        $response = $this->getJson("/api/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['body' => 'Public Comment']);
    }

    public function test_unauthenticated_user_cannot_create_post(): void
    {
        $response = $this->postJson('/api/posts', [
            'title' => 'New Post',
            'body' => 'Content',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_update_post(): void
    {
        $post = Post::factory()->create();

        $response = $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_delete_post(): void
    {
        $post = Post::factory()->create();

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_create_comment(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/comments", [
            'body' => 'New Comment',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_update_comment(): void
    {
        $comment = Comment::factory()->create();

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'body' => 'Updated Comment',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_delete_comment(): void
    {
        $comment = Comment::factory()->create();

        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_my_posts(): void
    {
        $response = $this->getJson('/api/my-posts');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_still_access_public_endpoints(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'total',
            ]);
    }

    public function test_public_endpoints_include_rate_limit_headers(): void
    {
        Post::factory()->create();

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    public function test_rate_limit_is_enforced_on_posts_list(): void
    {
        Post::factory()->create();

        // Make 61 requests (rate limit is 60 per minute)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/posts');
            
            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                // 61st request should be rate limited
                $response->assertStatus(429);
            }
        }
    }

    public function test_rate_limit_is_enforced_on_posts_search(): void
    {
        Post::factory()->create(['title' => 'Test Post']);

        // Make 61 requests
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/posts/search?q=test');
            
            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429);
            }
        }
    }

    public function test_rate_limit_is_enforced_on_single_post(): void
    {
        $post = Post::factory()->create();

        // Make 61 requests
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson("/api/posts/{$post->id}");
            
            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429);
            }
        }
    }

    public function test_rate_limit_is_enforced_on_comments_list(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->forPost($post)->create();

        // Make 61 requests
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson("/api/posts/{$post->id}/comments");
            
            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429);
            }
        }
    }

    public function test_rate_limit_is_enforced_on_single_comment(): void
    {
        $comment = Comment::factory()->create();

        // Make 61 requests
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson("/api/comments/{$comment->id}");
            
            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429);
            }
        }
    }

    public function test_rate_limit_response_includes_retry_after_header(): void
    {
        Post::factory()->create();

        // Trigger rate limit
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/posts');
        }

        // Last response should have Retry-After header
        $response->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_public_posts_include_author_information(): void
    {
        $author = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Post::factory()->forAuthor($author)->create();

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'author' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ]
            ]);
    }

    public function test_public_comments_include_author_information(): void
    {
        $author = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $post = Post::factory()->create();
        Comment::factory()->forPost($post)->forAuthor($author)->create();

        $response = $this->getJson("/api/posts/{$post->id}/comments");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
            ]);
    }

    public function test_public_search_works_with_all_filters(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel Tutorial',
            'published_at' => '2025-10-15'
        ]);
        Post::factory()->draft()->create([
            'title' => 'Laravel Guide'
        ]);

        $response = $this->getJson('/api/posts/search?q=laravel&status=published&published_at[from]=2025-10-01');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('results.total'));
    }

    public function test_public_endpoints_return_paginated_results(): void
    {
        Post::factory()->count(30)->create();

        $response = $this->getJson('/api/posts?per_page=10');

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(30, $response->json('total'));
        $this->assertEquals(10, $response->json('per_page'));
    }
}

