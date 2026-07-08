<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            // 7.1 استخراج الأنماط من القالب المرجعي
            ['category' => 'extraction', 'order' => 1, 'name' => 'CSS Scan / CSS Peeper', 'usage' => 'استخراج CSS أي عنصر بنقرة واحدة', 'note' => 'إضافة متصفح', 'is_mandatory' => false],
            ['category' => 'extraction', 'order' => 2, 'name' => 'Fonts Ninja / WhatFont', 'usage' => 'تحديد الخطوط وأوزانها فوراً', 'note' => 'إضافة متصفح', 'is_mandatory' => false],
            ['category' => 'extraction', 'order' => 3, 'name' => 'ColorZilla', 'usage' => 'سحب الألوان + استخراج لوحة الألوان الكاملة', 'note' => 'إضافة متصفح', 'is_mandatory' => false],
            ['category' => 'extraction', 'order' => 4, 'name' => 'Responsively App', 'usage' => 'عرض الأصل والموقع الجديد بعدة مقاسات جنباً إلى جنب', 'note' => 'تطبيق مجاني', 'is_mandatory' => false],
            ['category' => 'extraction', 'order' => 5, 'name' => 'DevTools', 'usage' => 'فحص قيم الـ Spacing والـ Transitions بدقة', 'note' => 'مدمجة', 'is_mandatory' => false],

            // 7.2 داخل WordPress
            ['category' => 'wordpress', 'order' => 1, 'name' => 'Elementor Site Settings', 'usage' => 'تعريف كل الألوان والخطوط مرة واحدة (حجر الأساس)', 'note' => 'إلزامي', 'is_mandatory' => true],
            ['category' => 'wordpress', 'order' => 2, 'name' => 'Elementor Kit / Template Export', 'usage' => 'نقل مكتبة الأقسام بين المشاريع (الـ Base Setup)', 'note' => 'إلزامي', 'is_mandatory' => true],
            ['category' => 'wordpress', 'order' => 3, 'name' => 'Spectra', 'usage' => 'للمواقع البسيطة والمتوسطة — أخف لأنه مبني على Gutenberg', 'note' => 'لا يُخلط مع Elementor في نفس الموقع', 'is_mandatory' => false],
            ['category' => 'wordpress', 'order' => 4, 'name' => 'Code Snippets', 'usage' => 'أي CSS/PHP مخصص بدل تعديل ملفات القالب', 'note' => 'معتمدة', 'is_mandatory' => false],

            // 7.3 الأداء والقياس
            ['category' => 'performance', 'order' => 1, 'name' => 'Perfmatters / Asset CleanUp', 'usage' => 'تعطيل ملفات CSS/JS غير المستخدمة لكل صفحة', 'note' => 'معتمدة', 'is_mandatory' => false],
            ['category' => 'performance', 'order' => 2, 'name' => 'FlyingPress / WP Rocket', 'usage' => 'الكاش والتحسين العام', 'note' => 'واحدة فقط لكل موقع', 'is_mandatory' => false],
            ['category' => 'performance', 'order' => 3, 'name' => 'Converter for Media', 'usage' => 'تحويل تلقائي للصور إلى WebP/AVIF', 'note' => 'معتمدة', 'is_mandatory' => false],
            ['category' => 'performance', 'order' => 4, 'name' => 'Screaming Frog', 'usage' => 'فحص SEO تقني شامل قبل الإطلاق وبعده (روابط، فهرسة، عناوين، Meta)', 'note' => 'إلزامي مرتين لكل مشروع', 'is_mandatory' => true],
            ['category' => 'performance', 'order' => 5, 'name' => 'PageSpeed Insights + GTmetrix', 'usage' => 'توثيق نتيجة القالب الأصلي مقابل الموقع النهائي', 'note' => 'جزء إلزامي من التسليم', 'is_mandatory' => true],
        ];

        foreach ($tools as $tool) {
            Tool::updateOrCreate(
                ['category' => $tool['category'], 'name' => $tool['name']],
                $tool
            );
        }
    }
}
