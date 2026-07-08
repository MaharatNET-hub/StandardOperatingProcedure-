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
            ['role' => User::ROLE_ADMIN],
            [
                'name' => 'م. رهف جمول',
                'email' => 'rahaf@maharatnet.com',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['role' => User::ROLE_IT_SPECIALIST],
            [
                'name' => 'م. أحمد',
                'email' => 'it@maharatnet.com',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['role' => User::ROLE_QA_REVIEWER],
            [
                'name' => 'مراجع الجودة',
                'email' => 'qa@maharatnet.com',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['role' => User::ROLE_DEVELOPER],
            [
                'name' => 'مبرمج',
                'email' => 'support@maharatnet.com',
                'password' => Hash::make('password'),
            ]
        );
    }
}
