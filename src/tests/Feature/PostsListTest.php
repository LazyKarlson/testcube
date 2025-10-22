<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostsListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders to set up roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_unauthenticated_user_cannot_access_posts_list(): void
    {
        $response = $this->getJson('/api/posts');

        // Posts are public, so unauthenticated users can access them
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_posts_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'total',
                'per_page',
            ]);
    }

    public function test_posts_list_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();

        $post = Post::factory()->forAuthor($author)->published()->create();
        Comment::factory()->forPost($post)->forAuthor($user)->create();

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'status',
                        'body',
                        'created_at',
                        'published_at',
                        'author' => ['name', 'email'],
                        'comments_count',
                        'last_comment',
                    ],
                ],
            ]);
    }

    public function test_posts_list_includes_author_information(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

        Post::factory()->forAuthor($author)->create(['title' => 'Test Post']);

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'author' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    }

    public function test_posts_list_includes_comments_count(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Comment::factory()->count(5)->forPost($post)->create();

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200);

        $postData = collect($response->json('data'))->firstWhere('id', $post->id);
        $this->assertEquals(5, $postData['comments_count']);
    }

    public function test_posts_list_includes_last_comment(): void
    {
        $user = User::factory()->create();
        $commentAuthor = User::factory()->create(['name' => 'Jane Smith']);
        $post = Post::factory()->create();

        Comment::factory()->forPost($post)->create(['body' => 'First comment']);
        Comment::factory()->forPost($post)->forAuthor($commentAuthor)->create(['body' => 'Last comment']);

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200);

        $postData = collect($response->json('data'))->firstWhere('id', $post->id);
        $this->assertEquals('Last comment', $postData['last_comment']['body']);
        $this->assertEquals('Jane Smith', $postData['last_comment']['author_name']);
    }

    public function test_posts_list_shows_null_for_last_comment_when_no_comments(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200);

        $postData = collect($response->json('data'))->firstWhere('id', $post->id);
        $this->assertNull($postData['last_comment']);
    }

    public function test_posts_list_default_pagination_is_25(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(30)->create();

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200);
        $this->assertEquals(25, count($response->json('data')));
        $this->assertEquals(25, $response->json('per_page'));
    }

    public function test_posts_list_custom_per_page(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(60)->create();

        $response = $this->actingAs($user)->getJson('/api/posts?per_page=50');

        $response->assertStatus(200);
        $this->assertEquals(50, count($response->json('data')));
        $this->assertEquals(50, $response->json('per_page'));
    }

    public function test_posts_list_per_page_max_is_100(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts?per_page=150');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_posts_list_default_sort_by_published_at_desc(): void
    {
        $user = User::factory()->create();

        $post1 = Post::factory()->create(['published_at' => now()->subDays(3)]);
        $post2 = Post::factory()->create(['published_at' => now()->subDays(1)]);
        $post3 = Post::factory()->create(['published_at' => now()->subDays(2)]);

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertEquals($post2->id, $ids[0]); // Most recent first
    }

    public function test_posts_list_can_sort_by_title_asc(): void
    {
        $user = User::factory()->create();

        Post::factory()->create(['title' => 'Zebra Post']);
        Post::factory()->create(['title' => 'Apple Post']);
        Post::factory()->create(['title' => 'Mango Post']);

        $response = $this->actingAs($user)->getJson('/api/posts?sort_by=title&sort_order=asc');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertEquals('Apple Post', $titles[0]);
        $this->assertEquals('Mango Post', $titles[1]);
        $this->assertEquals('Zebra Post', $titles[2]);
    }

    public function test_posts_list_can_sort_by_title_desc(): void
    {
        $user = User::factory()->create();

        Post::factory()->create(['title' => 'Zebra Post']);
        Post::factory()->create(['title' => 'Apple Post']);
        Post::factory()->create(['title' => 'Mango Post']);

        $response = $this->actingAs($user)->getJson('/api/posts?sort_by=title&sort_order=desc');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertEquals('Zebra Post', $titles[0]);
        $this->assertEquals('Mango Post', $titles[1]);
        $this->assertEquals('Apple Post', $titles[2]);
    }

    public function test_posts_list_can_sort_by_created_at(): void
    {
        $user = User::factory()->create();

        $post1 = Post::factory()->create(['created_at' => now()->subDays(3)]);
        $post2 = Post::factory()->create(['created_at' => now()->subDays(1)]);
        $post3 = Post::factory()->create(['created_at' => now()->subDays(2)]);

        $response = $this->actingAs($user)->getJson('/api/posts?sort_by=created_at&sort_order=desc');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertEquals($post2->id, $ids[0]); // Most recent first
    }

    public function test_posts_list_rejects_invalid_sort_by(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts?sort_by=invalid_field');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    }

    public function test_posts_list_rejects_invalid_sort_order(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts?sort_order=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort_order']);
    }

    public function test_posts_list_pagination_works_correctly(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(30)->create();

        $page1 = $this->actingAs($user)->getJson('/api/posts?per_page=10&page=1');
        $page2 = $this->actingAs($user)->getJson('/api/posts?per_page=10&page=2');

        $page1->assertStatus(200);
        $page2->assertStatus(200);

        $this->assertEquals(1, $page1->json('current_page'));
        $this->assertEquals(2, $page2->json('current_page'));
        $this->assertEquals(30, $page1->json('total'));
        $this->assertEquals(30, $page2->json('total'));

        // Ensure different posts on different pages
        $page1Ids = collect($page1->json('data'))->pluck('id')->toArray();
        $page2Ids = collect($page2->json('data'))->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
    }

    public function test_posts_list_includes_both_published_and_draft_posts(): void
    {
        $user = User::factory()->create();

        Post::factory()->published()->create(['title' => 'Published Post']);
        Post::factory()->draft()->create(['title' => 'Draft Post']);

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertContains('Published Post', $titles);
        $this->assertContains('Draft Post', $titles);
    }

    public function test_posts_list_shows_null_published_at_for_drafts(): void
    {
        $user = User::factory()->create();

        $draft = Post::factory()->draft()->create();

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200);

        $draftData = collect($response->json('data'))->firstWhere('id', $draft->id);
        $this->assertEquals('draft', $draftData['status']);
        $this->assertNull($draftData['published_at']);
    }
}
