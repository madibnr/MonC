<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create Superadmin
        User::updateOrCreate(
            ['email' => 'superadmin@monc.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_SUPERADMIN,
                'is_active' => true,
            ]
        );

        // Create Admin IT
        User::updateOrCreate(
            ['email' => 'admin@monc.local'],
            [
                'name' => 'Admin IT',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_ADMIN_IT,
                'is_active' => true,
            ]
        );

        // Create Operator
        User::updateOrCreate(
            ['email' => 'operator@monc.local'],
            [
                'name' => 'Operator',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_OPERATOR,
                'is_active' => true,
            ]
        );

        // Create Auditor
        User::updateOrCreate(
            ['email' => 'auditor@monc.local'],
            [
                'name' => 'Auditor',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_AUDITOR,
                'is_active' => true,
            ]
        );
    }
}
