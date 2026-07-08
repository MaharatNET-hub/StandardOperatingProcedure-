<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'cto@maharatnet.com'],
            [
                'name' => 'م. رهف جمول',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'it@maharatnet.com'],
            [
                'name' => 'م. أحمد',
                'password' => Hash::make('password'),
                'role' => User::ROLE_IT_SPECIALIST,
            ]
        );

        User::updateOrCreate(
            ['email' => 'qa@maharatnet.com'],
            [
                'name' => 'مراجع الجودة',
                'password' => Hash::make('password'),
                'role' => User::ROLE_QA_REVIEWER,
            ]
        );

        User::updateOrCreate(
            ['email' => 'developer@maharatnet.com'],
            [
                'name' => 'مبرمج',
                'password' => Hash::make('password'),
                'role' => User::ROLE_DEVELOPER,
            ]
        );
    }
}
