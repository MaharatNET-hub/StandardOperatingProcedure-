<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PageSpeedService
{
    private const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * Run a Lighthouse/PageSpeed analysis for one strategy (mobile|desktop)
     * and return a simplified score + top failing audits (in Arabic).
     */
    public function analyze(string $url, string $strategy): array
    {
        $apiKey = Setting::get(Setting::KEY_PAGESPEED_API_KEY);

        if (! $apiKey) {
            throw new RuntimeException('لم يتم إعداد مفتاح PageSpeed API بعد — أضفه من صفحة الإعدادات أولاً.');
        }

        $response = Http::timeout(90)->get(self::ENDPOINT, [
            'url' => $url,
            'key' => $apiKey,
            'strategy' => $strategy,
            'category' => 'performance',
            'locale' => 'ar',
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('error.message') ?? 'تعذر الاتصال بخدمة PageSpeed Insights.'
            );
        }

        $data = $response->json();
        $performance = $data['lighthouseResult']['categories']['performance'] ?? null;
        $audits = $data['lighthouseResult']['audits'] ?? [];
        $refs = $performance['auditRefs'] ?? [];

        $issues = [];
        foreach ($refs as $ref) {
            $audit = $audits[$ref['id']] ?? null;
            if (! $audit) {
                continue;
            }

            $mode = $audit['scoreDisplayMode'] ?? '';
            if (in_array($mode, ['notApplicable', 'informative', 'manual'], true)) {
                continue;
            }

            $score = $audit['score'] ?? null;
            if ($score !== null && $score >= 0.9) {
                continue; // passed
            }

            $issues[] = [
                'id' => $ref['id'],
                'title' => $audit['title'] ?? $ref['id'],
                'description' => $audit['description'] ?? '',
                'display_value' => $audit['displayValue'] ?? null,
            ];
        }

        return [
            'score' => $performance ? (int) round(($performance['score'] ?? 0) * 100) : null,
            'issues' => array_slice($issues, 0, 8),
        ];
    }
}
