<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_INITIAL_EMAIL', 'admin@laparoleeternelle.com');
        $admin = User::where('email', $email)->first();

        if (! $admin) {
            $password = env('ADMIN_INITIAL_PASSWORD', Str::random(40));

            $admin = User::create([
                'username' => env('ADMIN_INITIAL_USERNAME', 'admin'),
                'full_name' => 'Administrateur Principal',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'status' => 'active',
                'role_assigned_by' => 'system',
                'role_assigned_at' => now(),
            ]);
        }

        $admin->forceFill([
            'role' => 'admin',
            'status' => 'active',
            'is_primary_admin' => true,
        ])->save();
    }
}
