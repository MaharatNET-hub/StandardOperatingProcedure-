<?php

namespace App\Services;

use RuntimeException;

class ScreamingFrogImportService
{
    /**
     * Parse a Screaming Frog "Internal > HTML" export CSV and run the
     * subset of the SOP's section-4 (SEO) checklist that is objectively
     * verifiable from that report: H1 count, noindex, self-canonical,
     * broken links, temporary redirects, and title/meta length+uniqueness.
     *
     * Returns ['total_urls' => int, 'checks' => [ [key, category_code,
     * order, label_ar, status, summary, affected: string[]], ... ]].
     */
    public function parse(string $filePath): array
    {
        $rows = $this->readCsv($filePath);

        if (empty($rows)) {
            throw new RuntimeException('الملف فارغ أو غير قابل للقراءة.');
        }

        $checks = [
            $this->checkMultipleH1($rows),
            $this->checkNoindex($rows),
            $this->checkSelfCanonical($rows),
            $this->checkBrokenLinks($rows),
            $this->checkTemporaryRedirects($rows),
            $this->checkTitleAndMeta($rows),
            $this->checkDuplicateContent($rows),
        ];

        return [
            'total_urls' => count($rows),
            'checks' => array_values(array_filter($checks)),
        ];
    }

    private function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new RuntimeException('تعذر فتح الملف.');
        }

        // Strip a UTF-8 BOM if present so the first header isn't mis-read.
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = null;
        $rows = [];

        while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($header === null) {
                // Some exports include a title line before the real header;
                // keep skipping until we find the row containing "Address".
                if (! in_array('Address', $line, true) && ! $this->containsCaseInsensitive($line, 'address')) {
                    continue;
                }
                $header = array_map(fn ($h) => trim((string) $h), $line);

                continue;
            }

            if (count($line) < 2) {
                continue;
            }

            $row = [];
            foreach ($header as $i => $key) {
                $row[$this->normalizeKey($key)] = $line[$i] ?? '';
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function containsCaseInsensitive(array $line, string $needle): bool
    {
        foreach ($line as $value) {
            if (strtolower(trim((string) $value)) === $needle) {
                return true;
            }
        }

        return false;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(trim($key));
    }

    private function col(array $row, string ...$candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $key = $this->normalizeKey($candidate);
            if (array_key_exists($key, $row) && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function make(string $key, string $categoryCode, int $order, string $label, bool $pass, string $summary, array $affected): array
    {
        return [
            'key' => $key,
            'category_code' => $categoryCode,
            'order' => $order,
            'label_ar' => $label,
            'status' => $pass ? 'pass' : 'fail',
            'summary' => $summary,
            'affected' => array_slice($affected, 0, 30),
            'affected_total' => count($affected),
        ];
    }

    private function checkMultipleH1(array $rows): array
    {
        $affected = [];
        foreach ($rows as $row) {
            $address = $this->col($row, 'address');
            $statusCode = $this->col($row, 'status code');
            if (! $address || $statusCode !== '200') {
                continue;
            }
            $h1First = $this->col($row, 'h1-1', 'h1');
            $h1Second = $this->col($row, 'h1-2');

            if (! $h1First) {
                $affected[] = "{$address} — لا يوجد H1 على الإطلاق";
            } elseif ($h1Second) {
                $affected[] = "{$address} — أكثر من H1 واحد";
            }
        }

        return $this->make(
            'multiple_h1', '4.1', 1, 'عنوان H1 واحد فقط لكل صفحة',
            empty($affected),
            empty($affected) ? 'كل الصفحات تحتوي H1 واحد بالضبط.' : count($affected).' صفحة فيها مشكلة بعدد الـ H1.',
            $affected
        );
    }

    private function checkNoindex(array $rows): array
    {
        $affected = [];
        foreach ($rows as $row) {
            $address = $this->col($row, 'address');
            $status = $this->col($row, 'indexability status', 'indexability status 1');
            if ($address && $status && stripos($status, 'noindex') !== false) {
                $affected[] = $address;
            }
        }

        return $this->make(
            'noindex', '4.2', 2, 'لا وسم noindex على أي صفحة مهمة',
            empty($affected),
            empty($affected) ? 'لا صفحات محجوبة بوسم noindex.' : count($affected).' صفحة محجوبة بوسم noindex — تأكد أنها مقصودة.',
            $affected
        );
    }

    private function checkSelfCanonical(array $rows): array
    {
        $affected = [];
        foreach ($rows as $row) {
            $address = $this->col($row, 'address');
            $statusCode = $this->col($row, 'status code');
            if (! $address || $statusCode !== '200') {
                continue;
            }
            $canonical = $this->col($row, 'canonical link element 1', 'canonical');
            if (! $canonical) {
                $affected[] = "{$address} — لا يوجد Canonical";
            } elseif (rtrim($canonical, '/') !== rtrim($address, '/')) {
                $affected[] = "{$address} — يشير إلى: {$canonical}";
            }
        }

        return $this->make(
            'self_canonical', '4.2', 3, 'وسم Canonical ذاتي على كل صفحة',
            empty($affected),
            empty($affected) ? 'كل الصفحات لها Canonical ذاتي صحيح.' : count($affected).' صفحة بدون Canonical ذاتي صحيح.',
            $affected
        );
    }

    private function checkBrokenLinks(array $rows): array
    {
        $affected = [];
        foreach ($rows as $row) {
            $address = $this->col($row, 'address');
            $statusCode = (int) $this->col($row, 'status code');
            if ($address && ($statusCode === 404 || $statusCode === 410 || ($statusCode >= 500 && $statusCode < 600))) {
                $affected[] = "{$address} — {$statusCode}";
            }
        }

        return $this->make(
            'broken_links', '4.3', 1, 'صفر روابط داخلية مكسورة (404)',
            empty($affected),
            empty($affected) ? 'لا روابط مكسورة.' : count($affected).' رابط مكسور.',
            $affected
        );
    }

    private function checkTemporaryRedirects(array $rows): array
    {
        $affected = [];
        foreach ($rows as $row) {
            $address = $this->col($row, 'address');
            $statusCode = $this->col($row, 'status code');
            if ($address && in_array($statusCode, ['302', '307', '308'], true)) {
                $affected[] = "{$address} — {$statusCode}";
            }
        }

        return $this->make(
            'temporary_redirects', '4.3', 3, 'التحويلات كلها 301 وليست 302',
            empty($affected),
            empty($affected) ? 'لا تحويلات مؤقتة.' : count($affected).' تحويل مؤقت (302/307/308) بدل 301 دائم.',
            $affected
        );
    }

    private function checkTitleAndMeta(array $rows): array
    {
        $affected = [];
        $titles = [];
        $descriptions = [];

        foreach ($rows as $row) {
            $address = $this->col($row, 'address');
            $statusCode = $this->col($row, 'status code');
            if (! $address || $statusCode !== '200') {
                continue;
            }

            $title = $this->col($row, 'title 1');
            $titleLen = (int) $this->col($row, 'title 1 length');
            $desc = $this->col($row, 'meta description 1');
            $descLen = (int) $this->col($row, 'meta description 1 length');

            if (! $title) {
                $affected[] = "{$address} — Title مفقود";
            } elseif ($titleLen > 0 && ($titleLen < 50 || $titleLen > 60)) {
                $affected[] = "{$address} — طول Title {$titleLen} حرف (المطلوب 50-60)";
            }
            if ($title) {
                $titles[strtolower($title)][] = $address;
            }

            if (! $desc) {
                $affected[] = "{$address} — Meta Description مفقود";
            } elseif ($descLen > 0 && ($descLen < 140 || $descLen > 160)) {
                $affected[] = "{$address} — طول Meta Description {$descLen} حرف (المطلوب 140-160)";
            }
            if ($desc) {
                $descriptions[strtolower($desc)][] = $address;
            }
        }

        foreach ($titles as $value => $addresses) {
            if (count($addresses) > 1) {
                $affected[] = 'Title مكرر على: '.implode('، ', $addresses);
            }
        }
        foreach ($descriptions as $value => $addresses) {
            if (count($addresses) > 1) {
                $affected[] = 'Meta Description مكرر على: '.implode('، ', $addresses);
            }
        }

        return $this->make(
            'title_meta', '4.5', 1, 'Title وMeta Description فريدان وبالطول الصحيح لكل صفحة',
            empty($affected),
            empty($affected) ? 'كل العناوين والأوصاف سليمة وفريدة.' : count($affected).' مشكلة بالعناوين/الأوصاف.',
            $affected
        );
    }

    private function checkDuplicateContent(array $rows): array
    {
        $byHash = [];
        $hasHashColumn = false;

        foreach ($rows as $row) {
            $hash = $this->col($row, 'hash');
            $address = $this->col($row, 'address');
            if ($hash && $address) {
                $hasHashColumn = true;
                $byHash[$hash][] = $address;
            }
        }

        if (! $hasHashColumn) {
            return [];
        }

        $affected = [];
        foreach ($byHash as $addresses) {
            if (count($addresses) > 1) {
                $affected[] = implode(' = ', $addresses);
            }
        }

        return $this->make(
            'duplicate_content', '4.5', 3, 'لا محتوى مكرر بين الصفحات',
            empty($affected),
            empty($affected) ? 'لا صفحات متطابقة المحتوى.' : count($affected).' مجموعة صفحات متطابقة المحتوى.',
            $affected
        );
    }
}
