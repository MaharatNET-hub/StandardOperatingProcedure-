<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the 4 built-in system roles, preserving their exact current behavior.
     */
    public function run(): void
    {
        $roles = [
            [
                'key' => User::ROLE_ADMIN,
                'label_ar' => 'مدير / CTO',
                'permissions' => array_keys(Role::PERMISSIONS),
                'is_system' => true,
            ],
            [
                'key' => User::ROLE_DEVELOPER,
                'label_ar' => 'مبرمج',
                'permissions' => [],
                'is_system' => true,
            ],
            [
                'key' => User::ROLE_QA_REVIEWER,
                'label_ar' => 'مراجع جودة (QA)',
                'permissions' => ['qa_review', 'view_all_projects'],
                'is_system' => true,
            ],
            [
                'key' => User::ROLE_IT_SPECIALIST,
                'label_ar' => 'أخصائي تقنية معلومات',
                'permissions' => ['decide_plugins', 'view_all_projects'],
                'is_system' => true,
            ],
            [
                'key' => 'ceo',
                'label_ar' => 'المدير التنفيذي',
                'permissions' => array_keys(Role::PERMISSIONS),
                'is_system' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['key' => $role['key']], $role);
        }
    }
}
