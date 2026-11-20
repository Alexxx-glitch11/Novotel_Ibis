<?php

namespace App\Services\Translation;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Penerjemah teks mentah. Hasilnya berurutan sesuai input; teks yang gagal diterjemahkan bernilai null.
 */
class TextTranslator
{
    /**
     * @param  list<string>  $texts
     * @return array<int, string|null>
     */
    public function translate(array $texts, string $from, string $to): array
    {
        if ($texts === []) {
            return [];
        }

        try {
            return match (config('translation.driver')) {
                'mymemory' => $this->myMemory($texts, $from, $to),
                'google_cloud' => $this->googleCloud($texts, $from, $to),
                'libretranslate' => $this->libreTranslate($texts, $from, $to),
                'google_free' => $this->googleFree($texts, $from, $to),
                default => array_fill(0, count($texts), null),
            };
        } catch (Throwable $e) {
            report($e);

            return array_fill(0, count($texts), null);
        }
    }

    /** Endpoint gratis MyMemory Translation API (tanpa API key, maks. ~500 byte per teks). */
    private function myMemory(array $texts, string $from, string $to): array
    {
        $timeout = (int) config('translation.timeout', 5);
        $chunkSize = max(1, (int) config('translation.concurrency', 4));
        $email = trim((string) config('translation.mymemory_email', ''));
        $result = array_fill(0, count($texts), null);

        $eligible = [];
        foreach ($texts as $index => $text) {
            if (strlen($text) <= 480) {
                $eligible[$index] = $text;
            }
        }

        foreach (array_chunk($eligible, $chunkSize, true) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk, $from, $to, $timeout, $email) {
                $requests = [];
                foreach ($chunk as $index => $text) {
                    $query = [
                        'q' => $text,
                        'langpair' => $from . '|' . $to,
                    ];
                    if ($email !== '') {
                        $query['de'] = $email;
                    }

                    $requests[] = $pool->as((string) $index)
                        ->timeout($timeout)
                        ->get('https://api.mymemory.translated.net/get', $query);
                }

                return $requests;
            });

            foreach ($chunk as $index => $text) {
                $response = $responses[(string) $index] ?? null;
                if (! $response instanceof \Illuminate\Http\Client\Response || ! $response->successful()) {
                    continue;
                }

                if ((int) $response->json('responseStatus') !== 200
                    || filter_var($response->json('quotaFinished'), FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $raw = $response->json('responseData.translatedText');
                if (! is_string($raw)) {
                    continue;
                }

                $decoded = html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $decoded = (string) preg_replace('/(?<!\w)_([A-Za-z])/', '$1', $decoded);
                if ($decoded === ''
                    || preg_match('/<[a-z\/]/i', $decoded)
                    || (mb_strlen($text) <= 20 && mb_strlen($decoded) > max(35, mb_strlen($text) * 3))
                    || str_contains($decoded, 'MYMEMORY WARNING')
                    || str_contains($decoded, 'QUERY LENGTH LIMIT')) {
                    continue;
                }

                $result[$index] = $decoded;
            }
        }

        return $result;
    }

    /** Endpoint publik Google Translate (tanpa API key). */
    private function googleFree(array $texts, string $from, string $to): array
    {
        $timeout = (int) config('translation.timeout', 4);
        $chunkSize = max(1, (int) config('translation.concurrency', 10));
        $result = array_fill(0, count($texts), null);

        foreach (array_chunk($texts, $chunkSize, true) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk, $from, $to, $timeout) {
                $requests = [];
                foreach ($chunk as $index => $text) {
                    $requests[] = $pool->as((string) $index)
                        ->timeout($timeout)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                        ->get('https://translate.googleapis.com/translate_a/single', [
                            'client' => 'gtx',
                            'sl' => $from,
                            'tl' => $to,
                            'dt' => 't',
                            'q' => $text,
                        ]);
                }

                return $requests;
            });

            foreach ($chunk as $index => $text) {
                $response = $responses[(string) $index] ?? null;
                if (! $response instanceof \Illuminate\Http\Client\Response || ! $response->successful()) {
                    continue;
                }

                $segments = $response->json('0');
                if (! is_array($segments)) {
                    continue;
                }

                $translated = '';
                foreach ($segments as $segment) {
                    $translated .= (string) ($segment[0] ?? '');
                }
                $result[$index] = trim($translated) !== '' ? $translated : null;
            }
        }

        return $result;
    }

    /** Google Cloud Translation API v2 (berbayar, butuh GOOGLE_TRANSLATE_KEY). */
    private function googleCloud(array $texts, string $from, string $to): array
    {
        $key = (string) config('translation.google_key');
        if ($key === '') {
            return array_fill(0, count($texts), null);
        }

        $response = Http::timeout((int) config('translation.timeout', 4) * 2)
            ->asJson()
            ->post('https://translation.googleapis.com/language/translate/v2?key=' . urlencode($key), [
                'q' => array_values($texts),
                'source' => $from,
                'target' => $to,
                'format' => 'text',
            ]);

        if (! $response->successful()) {
            return array_fill(0, count($texts), null);
        }

        $rows = $response->json('data.translations', []);
        $result = [];
        foreach (array_values($texts) as $i => $text) {
            $result[$i] = isset($rows[$i]['translatedText']) ? (string) $rows[$i]['translatedText'] : null;
        }

        return $result;
    }

    /** LibreTranslate (bisa dihosting sendiri). */
    private function libreTranslate(array $texts, string $from, string $to): array
    {
        $url = rtrim((string) config('translation.libretranslate_url'), '/') . '/translate';
        $response = Http::timeout((int) config('translation.timeout', 4) * 2)
            ->asJson()
            ->post($url, array_filter([
                'q' => array_values($texts),
                'source' => $from,
                'target' => $to,
                'format' => 'text',
                'api_key' => config('translation.libretranslate_key'),
            ], fn ($v) => $v !== null && $v !== ''));

        if (! $response->successful()) {
            return array_fill(0, count($texts), null);
        }

        $rows = $response->json('translatedText', []);
        $result = [];
        foreach (array_values($texts) as $i => $text) {
            $result[$i] = isset($rows[$i]) ? (string) $rows[$i] : null;
        }

        return $result;
    }
}
