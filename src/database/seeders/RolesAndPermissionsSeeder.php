<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Post permissions
            ['name' => 'create_posts', 'description' => 'Create posts'],
            ['name' => 'read_posts', 'description' => 'Read posts'],
            ['name' => 'update_posts', 'description' => 'Update posts'],
            ['name' => 'delete_posts', 'description' => 'Delete posts'],

            // Comment permissions
            ['name' => 'create_comments', 'description' => 'Create comments'],
            ['name' => 'read_comments', 'description' => 'Read comments'],
            ['name' => 'update_comments', 'description' => 'Update comments'],
            ['name' => 'delete_comments', 'description' => 'Delete comments'],

            // User permissions
            ['name' => 'create_users', 'description' => 'Create users'],
            ['name' => 'read_users', 'description' => 'Read users'],
            ['name' => 'update_users', 'description' => 'Update users'],
            ['name' => 'delete_users', 'description' => 'Delete users'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description']]
            );
        }

        // Create roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator with full access']
        );

        $editorRole = Role::firstOrCreate(
            ['name' => 'editor'],
            ['description' => 'Editor can manage posts and comments']
        );

        $authorRole = Role::firstOrCreate(
            ['name' => 'author'],
            ['description' => 'Author can manage only their own posts and comments']
        );

        $viewerRole = Role::firstOrCreate(
            ['name' => 'viewer'],
            ['description' => 'Viewer can only read posts and comments']
        );

        // Assign permissions to admin role (all permissions)
        $adminPermissions = Permission::all();
        $adminRole->permissions()->sync($adminPermissions->pluck('id'));

        // Assign permissions to editor role (posts and comments CRUD)
        $editorPermissions = Permission::whereIn('name', [
            'create_posts', 'read_posts', 'update_posts', 'delete_posts',
            'create_comments', 'read_comments', 'update_comments', 'delete_comments',
        ])->get();
        $editorRole->permissions()->sync($editorPermissions->pluck('id'));

        // Assign permissions to author role (CRUD own posts and comments)
        // Note: Ownership checks are handled in controllers
        $authorPermissions = Permission::whereIn('name', [
            'create_posts', 'read_posts', 'update_posts', 'delete_posts',
            'create_comments', 'read_comments', 'update_comments', 'delete_comments',
        ])->get();
        $authorRole->permissions()->sync($authorPermissions->pluck('id'));

        // Assign permissions to viewer role (only read posts and comments)
        $viewerPermissions = Permission::whereIn('name', [
            'read_posts', 'read_comments',
        ])->get();
        $viewerRole->permissions()->sync($viewerPermissions->pluck('id'));

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Admin role: All permissions');
        $this->command->info('Editor role: CRUD posts and comments');
        $this->command->info('Author role: CRUD own posts and comments (ownership enforced in controllers)');
        $this->command->info('Viewer role: Read posts and comments');
    }
}
