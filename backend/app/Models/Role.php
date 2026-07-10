<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const PERMISSIONS = [
        'manage_users' => 'إدارة الأعضاء (إضافة/تعديل/حذف)',
        'manage_roles' => 'إدارة الأدوار والصلاحيات',
        'manage_projects' => 'إدارة المشاريع (إنشاء/تعديل/حذف)',
        'view_all_projects' => 'الاطلاع على كل المشاريع (وليس المكلّف بها فقط)',
        'manage_checklist_template' => 'إدارة قالب قائمة التحقق',
        'decide_plugins' => 'الموافقة/رفض طلبات الإضافات',
        'qa_review' => 'تنفيذ مراجعة الجودة والاعتماد النهائي',
        'manage_settings' => 'إدارة إعدادات النظام (مفاتيح API)',
    ];

    protected $fillable = [
        'key',
        'label_ar',
        'permissions',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }
}
