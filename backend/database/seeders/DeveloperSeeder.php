<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * فريق المبرمجين: مبرمجو الووردبريس، ومبرمج البرمجة الخاصة، ومبرمج الفلاتر.
 *
 * مصمَّم ليعمل على قاعدة فارغة وعلى قاعدة فيها الفريق أصلاً:
 * - إن وُجد حساب للشخص نفسه (بالبريد أو بمطابقة الاسم) فلا يُنشأ حساب جديد،
 *   ولا يُلمس اسمه ولا بريده ولا كلمة مروره — يُملأ تخصّصه فقط إن كان فارغاً.
 * - وإن لم يوجد، يُنشأ حساب جديد ببريد افتراضي وكلمة مرور مؤقتة يجب تغييرها.
 * - المطابقة محصورة بأصحاب دور "مبرمج"، حتى لا يتحوّل حساب مدير أو مراجع
 *   جودة إلى مبرمج بسبب تشابه الأسماء.
 *
 * بهذا يمكن إعادة تشغيله مع كل نشر دون تكرار الحسابات أو ضياع أي تعديل.
 */
class DeveloperSeeder extends Seeder
{
    /**
     * الفريق: الاسم، البريد الافتراضي، والتخصّص.
     *
     * @var array<int, array{name: string, email: string, specialization: string}>
     */
    private const DEVELOPERS = [
        // مبرمجو الووردبريس
        ['name' => 'عبد الرحمن احمد', 'email' => 'abdulrahman@maharatnet.com', 'specialization' => 'wordpress'],
        ['name' => 'رفعت', 'email' => 'refaat@maharatnet.com', 'specialization' => 'wordpress'],
        ['name' => 'عمار', 'email' => 'ammar@maharatnet.com', 'specialization' => 'wordpress'],
        ['name' => 'إسلام', 'email' => 'eslam@maharatnet.com', 'specialization' => 'wordpress'],
        ['name' => 'محمد', 'email' => 'mohammad@maharatnet.com', 'specialization' => 'wordpress'],
        ['name' => 'خالد', 'email' => 'khaled@maharatnet.com', 'specialization' => 'wordpress'],

        // البرمجة الخاصة
        ['name' => 'عبد الرحمن أبو سمرا', 'email' => 'abdulrahman.abusamra@maharatnet.com', 'specialization' => 'custom_dev'],

        // تطبيقات الموبايل (فلاتر)
        ['name' => 'علي', 'email' => 'ali@maharatnet.com', 'specialization' => 'flutter'],
    ];

    /**
     * أسماء بديلة تُطابَق بها الحسابات المُدخلة يدوياً إن كُتب الاسم بصيغة
     * أخرى — مرتّبة من الأطول للأقصر حتى يُطابَق "أبو سمرا" قبل "عبد الرحمن".
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'عبدالرحمنابوسمرا' => 'عبد الرحمن أبو سمرا',
        'ابوسمرا' => 'عبد الرحمن أبو سمرا',
        'عبدالرحمناحمد' => 'عبد الرحمن احمد',
        'عبدالرحمن' => 'عبد الرحمن احمد',
    ];

    public function run(): void
    {
        foreach (self::DEVELOPERS as $developer) {
            $existing = $this->findExisting($developer);

            if ($existing) {
                // حساب قائم — يُملأ التخصّص الفارغ فقط، ولا يُمسّ أي شيء آخر.
                if (! $existing->specialization) {
                    $existing->update(['specialization' => $developer['specialization']]);
                }

                continue;
            }

            // لا نُنشئ حساباً ببريد محجوز لمستخدم آخر.
            if (User::where('email', $developer['email'])->exists()) {
                continue;
            }

            User::create([
                'name' => $developer['name'],
                'email' => $developer['email'],
                'password' => Hash::make('password'),
                'role' => User::ROLE_DEVELOPER,
                'specialization' => $developer['specialization'],
            ]);
        }

        $this->classifyRemainingDevelopers();
    }

    /**
     * يبحث عن حساب المبرمج نفسه: بالبريد الافتراضي أولاً، ثم بمطابقة الاسم
     * بعد توحيده (إزالة لقب "م." والمسافات وتوحيد الهمزات) أو أحد أسمائه
     * البديلة.
     *
     * @param  array{name: string, email: string, specialization: string}  $developer
     */
    private function findExisting(array $developer): ?User
    {
        $developers = User::where('role', User::ROLE_DEVELOPER)->get();

        if ($byEmail = $developers->firstWhere('email', $developer['email'])) {
            return $byEmail;
        }

        $keys = [$this->normalize($developer['name'])];
        foreach (self::ALIASES as $alias => $target) {
            if ($target === $developer['name']) {
                $keys[] = $alias;
            }
        }

        return $developers->first(
            fn (User $user) => in_array($this->normalize($user->name), $keys, true)
        );
    }

    /**
     * شبكة أمان: يصنّف أي مبرمج آخر بلا تخصّص إذا كان اسمه يحتوي أحد أسماء
     * الفريق — مثل حساب مُدخل باسم مركّب لم تلتقطه المطابقة التامة أعلاه.
     */
    private function classifyRemainingDevelopers(): void
    {
        $map = [];
        foreach (self::ALIASES as $alias => $target) {
            $map[$alias] = collect(self::DEVELOPERS)->firstWhere('name', $target)['specialization'];
        }
        foreach (self::DEVELOPERS as $developer) {
            $map[$this->normalize($developer['name'])] = $developer['specialization'];
        }

        // الأطول أولاً حتى لا يُطابَق "عبد الرحمن" قبل "عبد الرحمن أبو سمرا".
        uksort($map, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $unclassified = User::where('role', User::ROLE_DEVELOPER)
            ->whereNull('specialization')
            ->get();

        foreach ($unclassified as $user) {
            $name = $this->normalize($user->name);

            foreach ($map as $key => $specialization) {
                if (str_contains($name, $key)) {
                    $user->update(['specialization' => $specialization]);
                    break;
                }
            }
        }
    }

    private function normalize(string $name): string
    {
        $name = str_replace(['أ', 'إ', 'آ', 'ة', 'ى', 'ـ'], ['ا', 'ا', 'ا', 'ه', 'ي', ''], $name);
        $name = preg_replace('/^\s*م\s*\.\s*/u', '', $name);

        return preg_replace('/\s+/u', '', $name) ?? $name;
    }
}
