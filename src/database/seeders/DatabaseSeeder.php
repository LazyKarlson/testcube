<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // Create test users with roles
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        $editor = User::factory()->create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
        ]);
        $editor->assignRole('editor');

        $author = User::factory()->create([
            'name' => 'Author User',
            'email' => 'author@example.com',
        ]);
        $author->assignRole('author');

        $viewer = User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
        ]);
        $viewer->assignRole('viewer');

        $this->command->info('Test users created:');
        $this->command->info('Admin: admin@example.com / password');
        $this->command->info('Editor: editor@example.com / password');
        $this->command->info('Author: author@example.com / password');
        $this->command->info('Viewer: viewer@example.com / password');
    }
}
