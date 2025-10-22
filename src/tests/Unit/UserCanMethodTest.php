<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCanMethodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $createPosts = Permission::create([
            'name' => 'create_posts',
            'description' => 'Create posts',
        ]);

        $readPosts = Permission::create([
            'name' => 'read_posts',
            'description' => 'Read posts',
        ]);

        // Create role with permissions
        $role = Role::create([
            'name' => 'author',
            'description' => 'Author role',
        ]);

        $role->permissions()->attach([$createPosts->id, $readPosts->id]);
    }

    public function test_can_method_with_two_string_arguments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('author');

        // Test with action and resource format
        $this->assertTrue($user->can('create', 'posts'));
        $this->assertTrue($user->can('read', 'posts'));
        $this->assertFalse($user->can('delete', 'posts'));
    }

    public function test_can_method_with_single_permission_string(): void
    {
        $user = User::factory()->create();
        $user->assignRole('author');

        // Test with full permission name
        $this->assertTrue($user->can('create_posts'));
        $this->assertTrue($user->can('read_posts'));
        $this->assertFalse($user->can('delete_posts'));
    }

    public function test_can_method_is_compatible_with_parent_signature(): void
    {
        $user = User::factory()->create();
        $user->assignRole('author');

        // Test that method signature is compatible
        // This should not throw a type error
        $result = $user->can('create_posts', []);
        $this->assertTrue($result);
    }

    public function test_has_permission_method_still_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('author');

        // Test hasPermission method
        $this->assertTrue($user->hasPermission('create_posts'));
        $this->assertTrue($user->hasPermission('read_posts'));
        $this->assertFalse($user->hasPermission('delete_posts'));
    }

    public function test_can_method_with_user_without_permissions(): void
    {
        $user = User::factory()->create();
        // User has no roles

        $this->assertFalse($user->can('create', 'posts'));
        $this->assertFalse($user->can('create_posts'));
    }

    public function test_can_method_with_multiple_roles(): void
    {
        // Create another permission
        $updatePosts = Permission::create([
            'name' => 'update_posts',
            'description' => 'Update posts',
        ]);

        // Create editor role
        $editorRole = Role::create([
            'name' => 'editor',
            'description' => 'Editor role',
        ]);

        $editorRole->permissions()->attach([$updatePosts->id]);

        $user = User::factory()->create();
        $user->assignRole('author');
        $user->assignRole('editor');

        // Should have permissions from both roles
        $this->assertTrue($user->can('create', 'posts')); // from author
        $this->assertTrue($user->can('update', 'posts')); // from editor
    }
}
