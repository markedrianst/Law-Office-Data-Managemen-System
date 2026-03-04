<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Call other seeders FIRST
        $this->call([
            RoleSeeder::class,
            CaseCategorySeeder::class, // Make sure this matches your actual class name
            CaseStageSeeder::class,
            CourtSeeder::class,
            DocumentsSeeder::class,
            // CaseSeeder::class, // Uncomment only if this exists
        ]);

        // Get roles safely with matching case
        $adminRole  = Role::where('name', 'admin')->first();
        $clerkRole  = Role::where('name', 'clerk')->first();
        $lawyerRole = Role::where('name', 'lawyer')->first();

        // Check if roles exist before creating users
        if ($adminRole && $clerkRole && $lawyerRole) {
            // Create users properly using Eloquent
            User::create([
                'role_id' => $adminRole->id,
                'full_name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'password_hash' => bcrypt('password123'),
                'status' => 'active',
            ]);

            User::create([
                'role_id' => $clerkRole->id,
                'full_name' => 'Clerk User',
                'email' => 'clerk@gmail.com',
                'password_hash' => bcrypt('password123'),
                'status' => 'active',
            ]);

            User::create([
                'role_id' => $lawyerRole->id,
                'full_name' => 'Lawyer User',
                'email' => 'lawyer@gmail.com',
                'password_hash' => bcrypt('password123'),
                'status' => 'active',
            ]);
        } else {
            $this->command->error('Roles not found! Make sure RoleSeeder ran successfully.');
        }
    }
}