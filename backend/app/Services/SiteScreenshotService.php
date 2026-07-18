<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * يلتقط لقطة شاشة فعلية للصفحة الرئيسية عبر متصفح Chromium بوضع headless
 * (بدون أي اعتماديات Node/Playwright — ينفّذ الملف التنفيذي مباشرة). يفشل
 * بهدوء (يرجع null) لو المتصفح غير مثبّت على البيئة أو انتهت المهلة، بحيث
 * لا يوقف باقي عملية التحليل الفني.
 */
class SiteScreenshotService
{
    public function capture(string $url): ?string
    {
        $binary = $this->resolveBinary();
        if (! $binary) {
            return null;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'maharat_shot_').'.png';

        try {
            Process::timeout(25)->run([
                $binary,
                '--headless=new',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--hide-scrollbars',
                '--window-size=1280,900',
                '--virtual-time-budget=8000',
                '--screenshot='.$tmpFile,
                $url,
            ]);

            if (! is_file($tmpFile) || filesize($tmpFile) === 0) {
                return null;
            }

            return base64_encode((string) file_get_contents($tmpFile));
        } catch (Throwable $e) {
            return null;
        } finally {
            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    private function resolveBinary(): ?string
    {
        $configured = env('CHROME_PATH');
        if ($configured && is_file($configured)) {
            return $configured;
        }

        foreach (['/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome', '/opt/pw-browsers/chromium'] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
