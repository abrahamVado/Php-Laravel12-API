<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => Str::lower('test@example.com'),
        ]);

        $managerRole = Role::where('name', 'manager')->first();

        if ($managerRole) {
            $user->roles()->sync([$managerRole->id]);
        }
    }
}
