<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * يصنّف حسابات المبرمجين الموجودة حسب تخصّصها (ووردبريس / برمجة خاصة /
 * فلاتر) بمطابقة الاسم.
 *
 * لا ينشئ أي حساب جديد ولا يلمس حساباً سبق تحديد تخصّصه يدوياً — فقط يملأ
 * التخصّص الفارغ لمن دوره "مبرمج"، حتى لا يضطر أحد لإعادة إدخال البيانات
 * ولا تُستبدل أي اختيارات تمّت من صفحة فريق العمل.
 */
class DeveloperSpecializationSeeder extends Seeder
{
    /**
     * الاسم (بعد التوحيد) => التخصّص. مرتّبة من الاسم الأطول للأقصر حتى
     * يُطابَق "عبد الرحمن أبو سمرا" قبل "عبد الرحمن".
     *
     * @var array<string, string>
     */
    private const NAME_MAP = [
        'عبدالرحمنابوسمرا' => 'custom_dev',
        'ابوسمرا' => 'custom_dev',
        'عبدالرحمناحمد' => 'wordpress',
        'عبدالرحمن' => 'wordpress',
        'اسلام' => 'wordpress',
        'رفعت' => 'wordpress',
        'عمار' => 'wordpress',
        'محمد' => 'wordpress',
        'خالد' => 'wordpress',
        'احمد' => 'wordpress',
        'علي' => 'flutter',
    ];

    public function run(): void
    {
        $developers = User::query()
            ->where('role', User::ROLE_DEVELOPER)
            ->whereNull('specialization')
            ->get();

        foreach ($developers as $developer) {
            $name = $this->normalize($developer->name);

            foreach (self::NAME_MAP as $key => $specialization) {
                if (str_contains($name, $key)) {
                    $developer->update(['specialization' => $specialization]);
                    break;
                }
            }
        }
    }

    /**
     * يوحّد الاسم قبل المطابقة: يزيل لقب "م." والمسافات والتطويل، ويوحّد
     * صور الألف والتاء المربوطة والياء، فيتطابق "م. عبد الرحمن" مع
     * "عبدالرحمن".
     */
    private function normalize(string $name): string
    {
        $name = str_replace(['أ', 'إ', 'آ', 'ة', 'ى', 'ـ'], ['ا', 'ا', 'ا', 'ه', 'ي', ''], $name);
        $name = preg_replace('/^\s*م\s*\.\s*/u', '', $name);

        return preg_replace('/\s+/u', '', $name) ?? $name;
    }
}
