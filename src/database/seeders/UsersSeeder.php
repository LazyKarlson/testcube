<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating users...');

        // Create 2 admin users
        $this->command->info('Creating 2 admin users...');
        for ($i = 1; $i <= 2; $i++) {
            $admin = User::factory()->create([
                'name' => "Admin User {$i}",
                'email' => "admin{$i}@example.com",
            ]);
            $admin->assignRole('admin');
        }

        // Create 5 editor users
        $this->command->info('Creating 5 editor users...');
        for ($i = 1; $i <= 5; $i++) {
            $editor = User::factory()->create([
                'name' => "Editor User {$i}",
                'email' => "editor{$i}@example.com",
            ]);
            $editor->assignRole('editor');
        }

        // Create 50 author users
        $this->command->info('Creating 50 author users...');
        for ($i = 1; $i <= 50; $i++) {
            $author = User::factory()->create([
                'name' => "Author User {$i}",
                'email' => "author{$i}@example.com",
            ]);
            $author->assignRole('author');
        }

        $this->command->info('');
        $this->command->info('✅ Users created successfully!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('- 2 Admin users: admin1@example.com, admin2@example.com');
        $this->command->info('- 5 Editor users: editor1@example.com ... editor5@example.com');
        $this->command->info('- 50 Author users: author1@example.com ... author50@example.com');
        $this->command->info('');
        $this->command->info('All passwords: password');
    }
}

