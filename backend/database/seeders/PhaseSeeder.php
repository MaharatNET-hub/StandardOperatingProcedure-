<?php

namespace Database\Seeders;

use App\Models\Phase;
use Illuminate\Database\Seeder;

class PhaseSeeder extends Seeder
{
    public function run(): void
    {
        $phases = [
            [
                'number' => 0,
                'name_ar' => 'التحليل البصري (Discovery)',
                'description' => 'عرض قوالب Envato المقترحة على العميل، اختيار الشكل دون شراء القالب (يكفي رابط Live Preview)، وإجراء تشريح بصري (الألوان، الخطوط، المسافات، أنواع الأقسام، أنماط التفاعل). الناتج الإلزامي: مستند Mini Style Guide يُعتمد قبل بدء أي بناء.',
                'estimated_duration' => 'نصف يوم',
                'order' => 0,
            ],
            [
                'number' => 1,
                'name_ar' => 'تجهيز الأساس (Foundation)',
                'description' => 'تنصيب Astra على بيئة نظيفة (PHP حديثة + Object Caching)، استخدام Base Setup الداخلي الموحّد، وتعريف Global Styles (الألوان + Typography + عرض الـ Container) قبل بناء أي صفحة.',
                'estimated_duration' => 'نصف يوم',
                'order' => 1,
            ],
            [
                'number' => 2,
                'name_ar' => 'بناء نظام التصميم (Design System)',
                'description' => 'بناء مكتبة أقسام (Header, Hero, Cards, CTA, Footer) كقوالب قابلة لإعادة الاستخدام. القاعدة: يُبنى المكوّن مرة واحدة ويُستخدم في كل الصفحات.',
                'estimated_duration' => 'يوم واحد',
                'order' => 2,
            ],
            [
                'number' => 3,
                'name_ar' => 'بناء الصفحات',
                'description' => 'ترتيب البناء: Header/Footer ← الصفحة الرئيسية ← الصفحات الداخلية ← صفحات النظام (404, Search). المراجعة جنباً إلى جنب مع الـ Preview الأصلي على شاشتين.',
                'estimated_duration' => '2-4 أيام',
                'order' => 3,
            ],
            [
                'number' => 4,
                'name_ar' => 'الضبط والتسليم',
                'description' => 'فحص Responsive على ثلاث نقاط كسر كحد أدنى، وقياس الأداء (Lighthouse/PageSpeed) وتوثيق النتيجة مقارنةً بالقالب الأصلي.',
                'estimated_duration' => 'يوم واحد',
                'order' => 4,
            ],
        ];

        foreach ($phases as $phase) {
            Phase::updateOrCreate(['number' => $phase['number']], $phase);
        }
    }
}
