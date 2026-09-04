<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@laparoleeternelle.com'],
            [
                'username' => 'admin',
                'full_name' => 'Administrateur Principal',
                'email' => 'admin@laparoleeternelle.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'role_assigned_by' => 'system',
                'role_assigned_at' => now(),
            ]
        );

        $admin->forceFill([
            'role' => 'admin',
            'status' => 'active',
            'is_primary_admin' => true,
        ])->save();
    }
}
