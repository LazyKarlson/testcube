<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostsSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders to set up roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_unauthenticated_user_cannot_search_posts(): void
    {
        $response = $this->getJson('/api/posts/search?q=test');

        $response->assertStatus(401);
    }

    public function test_search_requires_query_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_search_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['title' => 'Laravel Tutorial']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'query',
                'filters' => ['status', 'published_from', 'published_to'],
                'results' => [
                    'current_page',
                    'data',
                    'total',
                    'per_page',
                ]
            ]);
    }

    public function test_search_finds_posts_by_title(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->create(['title' => 'Laravel Tutorial', 'body' => 'Some content']);
        Post::factory()->create(['title' => 'PHP Basics', 'body' => 'Other content']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel');

        $response->assertStatus(200);
        $this->assertEquals('laravel', $response->json('query'));
        $this->assertEquals(1, $response->json('results.total'));
        $this->assertEquals('Laravel Tutorial', $response->json('results.data.0.title'));
    }

    public function test_search_finds_posts_by_body(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->create(['title' => 'My Post', 'body' => 'This is about Laravel framework']);
        Post::factory()->create(['title' => 'Another Post', 'body' => 'This is about PHP']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('results.total'));
        $this->assertEquals('My Post', $response->json('results.data.0.title'));
    }

    public function test_search_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->create(['title' => 'LARAVEL Tutorial']);
        Post::factory()->create(['title' => 'laravel Guide']);
        Post::factory()->create(['title' => 'LaRaVeL Tips']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('results.total'));
    }

    public function test_search_matches_partial_strings(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->create(['title' => 'Laravel Framework']);
        Post::factory()->create(['title' => 'Lara Croft']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=lara');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('results.total'));
    }

    public function test_search_can_filter_by_published_status(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->published()->create(['title' => 'Published Laravel Post']);
        Post::factory()->draft()->create(['title' => 'Draft Laravel Post']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel&status=published');

        $response->assertStatus(200);
        $this->assertEquals('published', $response->json('filters.status'));
        $this->assertEquals(1, $response->json('results.total'));
        $this->assertEquals('Published Laravel Post', $response->json('results.data.0.title'));
        $this->assertEquals('published', $response->json('results.data.0.status'));
    }

    public function test_search_can_filter_by_draft_status(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->published()->create(['title' => 'Published Laravel Post']);
        Post::factory()->draft()->create(['title' => 'Draft Laravel Post']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel&status=draft');

        $response->assertStatus(200);
        $this->assertEquals('draft', $response->json('filters.status'));
        $this->assertEquals(1, $response->json('results.total'));
        $this->assertEquals('Draft Laravel Post', $response->json('results.data.0.title'));
        $this->assertEquals('draft', $response->json('results.data.0.status'));
    }

    public function test_search_can_filter_by_published_at_from_date(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->create([
            'title' => 'Old Laravel Post',
            'published_at' => '2025-09-15'
        ]);
        Post::factory()->create([
            'title' => 'New Laravel Post',
            'published_at' => '2025-10-15'
        ]);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel&published_at[from]=2025-10-01');

        $response->assertStatus(200);
        $this->assertEquals('2025-10-01', $response->json('filters.published_from'));
        $this->assertEquals(1, $response->json('results.total'));
        $this->assertEquals('New Laravel Post', $response->json('results.data.0.title'));
    }

    public function test_search_can_filter_by_published_at_to_date(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->create([
            'title' => 'Old Laravel Post',
            'published_at' => '2025-09-15'
        ]);
        Post::factory()->create([
            'title' => 'New Laravel Post',
            'published_at' => '2025-10-15'
        ]);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel&published_at[to]=2025-09-30');

        $response->assertStatus(200);
        $this->assertEquals('2025-09-30', $response->json('filters.published_to'));
        $this->assertEquals(1, $response->json('results.total'));
        $this->assertEquals('Old Laravel Post', $response->json('results.data.0.title'));
    }

    public function test_search_can_filter_by_date_range(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->create(['title' => 'Post 1', 'published_at' => '2025-09-15']);
        Post::factory()->create(['title' => 'Post 2', 'published_at' => '2025-10-05']);
        Post::factory()->create(['title' => 'Post 3', 'published_at' => '2025-10-15']);
        Post::factory()->create(['title' => 'Post 4', 'published_at' => '2025-10-25']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=post&published_at[from]=2025-10-01&published_at[to]=2025-10-20');

        $response->assertStatus(200);
        $this->assertEquals('2025-10-01', $response->json('filters.published_from'));
        $this->assertEquals('2025-10-20', $response->json('filters.published_to'));
        $this->assertEquals(2, $response->json('results.total'));
        
        $titles = collect($response->json('results.data'))->pluck('title')->toArray();
        $this->assertContains('Post 2', $titles);
        $this->assertContains('Post 3', $titles);
    }

    public function test_search_validates_to_date_is_after_from_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=test&published_at[from]=2025-10-20&published_at[to]=2025-10-10');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['published_at.to']);
    }

    public function test_search_can_combine_all_filters(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->published()->create([
            'title' => 'Laravel Tutorial',
            'published_at' => '2025-10-15'
        ]);
        Post::factory()->draft()->create([
            'title' => 'Laravel Guide',
            'published_at' => null
        ]);
        Post::factory()->published()->create([
            'title' => 'Laravel Tips',
            'published_at' => '2025-09-15'
        ]);
        Post::factory()->published()->create([
            'title' => 'PHP Basics',
            'published_at' => '2025-10-10'
        ]);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel&status=published&published_at[from]=2025-10-01&published_at[to]=2025-10-31');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('results.total'));
        $this->assertEquals('Laravel Tutorial', $response->json('results.data.0.title'));
    }

    public function test_search_can_sort_by_title(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->create(['title' => 'Zebra Laravel']);
        Post::factory()->create(['title' => 'Apple Laravel']);
        Post::factory()->create(['title' => 'Mango Laravel']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel&sort_by=title&sort_order=asc');

        $response->assertStatus(200);
        
        $titles = collect($response->json('results.data'))->pluck('title')->toArray();
        $this->assertEquals('Apple Laravel', $titles[0]);
        $this->assertEquals('Mango Laravel', $titles[1]);
        $this->assertEquals('Zebra Laravel', $titles[2]);
    }

    public function test_search_can_sort_by_created_at(): void
    {
        $user = User::factory()->create();
        
        $post1 = Post::factory()->create(['title' => 'Post 1', 'created_at' => now()->subDays(3)]);
        $post2 = Post::factory()->create(['title' => 'Post 2', 'created_at' => now()->subDays(1)]);
        $post3 = Post::factory()->create(['title' => 'Post 3', 'created_at' => now()->subDays(2)]);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=post&sort_by=created_at&sort_order=desc');

        $response->assertStatus(200);
        
        $ids = collect($response->json('results.data'))->pluck('id')->toArray();
        $this->assertEquals($post2->id, $ids[0]); // Most recent first
    }

    public function test_search_includes_author_information(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        
        Post::factory()->forAuthor($author)->create(['title' => 'Laravel Post']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'author' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ]
            ]);
    }

    public function test_search_includes_comments_count(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['title' => 'Laravel Post']);
        
        Comment::factory()->count(5)->forPost($post)->create();

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel');

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('results.data.0.comments_count'));
    }

    public function test_search_includes_last_comment(): void
    {
        $user = User::factory()->create();
        $commentAuthor = User::factory()->create(['name' => 'Jane Smith']);
        $post = Post::factory()->create(['title' => 'Laravel Post']);
        
        Comment::factory()->forPost($post)->create(['body' => 'First comment']);
        Comment::factory()->forPost($post)->forAuthor($commentAuthor)->create(['body' => 'Last comment']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel');

        $response->assertStatus(200);
        $this->assertEquals('Last comment', $response->json('results.data.0.last_comment.body'));
        $this->assertEquals('Jane Smith', $response->json('results.data.0.last_comment.author_name'));
    }

    public function test_search_pagination_works(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->count(30)->create(['title' => 'Laravel Post']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel&per_page=10');

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('results.data')));
        $this->assertEquals(10, $response->json('results.per_page'));
        $this->assertEquals(30, $response->json('results.total'));
    }

    public function test_search_returns_empty_results_when_no_matches(): void
    {
        $user = User::factory()->create();
        
        Post::factory()->create(['title' => 'PHP Tutorial', 'body' => 'PHP content']);

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=laravel');

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('results.total'));
        $this->assertEmpty($response->json('results.data'));
    }

    public function test_search_rejects_invalid_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=test&status=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_search_rejects_invalid_date_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=test&published_at[from]=invalid-date');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['published_at.from']);
    }

    public function test_search_respects_per_page_limit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts/search?q=test&per_page=150');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }
}

