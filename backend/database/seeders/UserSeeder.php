<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * حسابات البدء الافتراضية. المطابقة تتم بالبريد الإلكتروني وليس بالدور —
 * فالمطابقة بالدور كانت تلتقط أول مستخدم يحمل ذلك الدور، ما يعني أن إعادة
 * النشر قد تستبدل اسم أحد المبرمجين الحقيقيين وبريده وكلمة مروره بحساب
 * "مبرمج" التجريبي. المطابقة بالبريد تُبقي هذا السيدر محصوراً في حساباته.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'rahaf@maharatnet.com'],
            [
                'name' => 'م. رهف جمول',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'it@maharatnet.com'],
            [
                'name' => 'م. أحمد',
                'role' => User::ROLE_IT_SPECIALIST,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'qa@maharatnet.com'],
            [
                'name' => 'مراجع الجودة',
                'role' => User::ROLE_QA_REVIEWER,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'support@maharatnet.com'],
            [
                'name' => 'مبرمج',
                'role' => User::ROLE_DEVELOPER,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'zaid@maharatnet.com'],
            [
                'name' => 'م. زيد',
                'role' => 'ceo',
                'password' => Hash::make('password'),
            ]
        );
    }
}
