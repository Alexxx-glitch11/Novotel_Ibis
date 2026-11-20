<?php

namespace App\Services;

use App\Services\Translation\TextTranslator;
use Illuminate\Support\Facades\Cache;

/**
 * Menerjemahkan teks tampilan HTML dari bahasa sumber (Indonesia) ke bahasa lain secara otomatis,
 * sehingga fitur/menu baru tidak perlu ditambahkan manual ke lang/*.
 *
 * Hasil terjemahan disimpan di storage/app/translations/{locale}.json (dapat diedit manual).
 */
class AutoTranslator
{
    /** Singkatan/kata yang tidak perlu diterjemahkan. */
    private const IGNORED = [
        'Rp', 'WIB', 'QR', 'CSV', 'PDF', 'OK', 'ID', 'EN', 'DINEA', 'RRMS',
        'Novotel', 'Ibis', 'ibis', 'Food Exchange', 'Ibis Kitchen', 'Novotel Food Exchange',
        'Admin', 'Manager', 'Finance', 'Indonesia', 'English', 'Indoor', 'Outdoor',
        'Outlet', 'OUTLET', 'Menu', 'Event', 'Email', 'Token', 'Status', 'Stop', 'Reset',
        'NOVOTEL', 'Good Food', 'Great Moments', 'Reservation Management', 'RESERVATION MANAGEMENT',
        'No-show', 'Paid', 'Unpaid', 'Pending',
        'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
        'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
        'January', 'February', 'March', 'April', 'June', 'July', 'August', 'September', 'October', 'November', 'December',
    ];

    /** @var array<string, array<string, string>> */
    private array $dictionaries = [];

    /** @var array<string, array<string, true>> */
    private array $knownTarget = [];

    public function __construct(private readonly TextTranslator $translator)
    {
    }

    public function enabledFor(string $locale): bool
    {
        return $locale !== config('translation.source', 'id')
            && in_array($locale, (array) config('translation.locales', ['en']), true);
    }

    public function translateHtml(string $html, string $locale): string
    {
        $this->load($locale);

        $tokens = preg_split(
            '~(<script\b.*?</script>|<style\b.*?</style>|<textarea\b.*?</textarea>|<pre\b.*?</pre>|<code\b.*?</code>|<!--.*?-->|<[^>]+>)~is',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        );
        if ($tokens === false) {
            return $html;
        }

        // Tahap 1: kumpulkan teks yang belum punya terjemahan.
        $missing = [];
        $this->walk($tokens, function (string $fragment) use (&$missing, $locale): string {
            if (! isset($this->dictionaries[$locale][$fragment]) && ! isset($this->knownTarget[$locale][$fragment])) {
                $missing[$fragment] = true;
            }

            return $fragment;
        }, $locale);

        if ($missing !== []) {
            $this->fetchMissing(array_keys($missing), $locale);
        }

        // Tahap 2: ganti teks dengan terjemahannya.
        return $this->walk($tokens, fn (string $fragment): string => $this->dictionaries[$locale][$fragment] ?? $fragment, $locale);
    }

    /** @param list<string> $tokens */
    private function walk(array $tokens, callable $translate, string $locale): string
    {
        $out = '';
        $skipTag = null;
        $skipDepth = 0;
        $voidTags = ['br', 'img', 'input', 'hr', 'meta', 'link', 'source', 'area', 'col', 'embed', 'wbr'];

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            if ($token[0] !== '<') {
                $out .= $skipTag !== null ? $token : $this->mapText($token, $translate, $locale);
                continue;
            }

            if (preg_match('~^<(script|style|textarea|pre|code|!--)~i', $token)) {
                $out .= $token;
                continue;
            }

            if ($skipTag !== null) {
                if (preg_match('~^<(/?)([a-z0-9-]+)~i', $token, $m) && strtolower($m[2]) === $skipTag) {
                    $skipDepth += $m[1] === '/' ? -1 : (str_ends_with(rtrim($token), '/>') ? 0 : 1);
                    if ($skipDepth <= 0) {
                        $skipTag = null;
                        $skipDepth = 0;
                    }
                }
                $out .= $token;
                continue;
            }

            if (preg_match('~^<([a-z0-9-]+)\b[^>]*?(\btranslate\s*=\s*["\']no["\']|\bdata-notranslate\b)~is', $token, $m)) {
                $tag = strtolower($m[1]);
                if (! in_array($tag, $voidTags, true) && ! str_ends_with(rtrim($token), '/>')) {
                    $skipTag = $tag;
                    $skipDepth = 1;
                }
                $out .= $token;
                continue;
            }

            $out .= $this->mapAttributes($token, $translate, $locale);
        }

        return $out;
    }

    private function mapText(string $token, callable $translate, string $locale): string
    {
        if (! preg_match('~^(\s*)(.*?)(\s*)$~su', $token, $m) || $m[2] === '') {
            return $token;
        }

        $decoded = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $translated = $this->translateString($decoded, $translate, $locale);
        if ($translated === $decoded) {
            return $token;
        }

        return $m[1] . htmlspecialchars($translated, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8', false) . $m[3];
    }

    private function mapAttributes(string $tag, callable $translate, string $locale): string
    {
        $isButtonInput = preg_match('~^<input\b[^>]*\btype\s*=\s*["\']?(submit|button|reset)~i', $tag) === 1;
        $names = $isButtonInput
            ? 'placeholder|title|aria-label|alt|value'
            : 'placeholder|title|aria-label|alt';

        return preg_replace_callback(
            '~(?<![\w-])(' . $names . ')\s*=\s*(["\'])(.*?)\2~is',
            function (array $m) use ($translate, $locale): string {
                $decoded = html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $translated = $this->translateString($decoded, $translate, $locale);
                if ($translated === $decoded) {
                    return $m[0];
                }

                return $m[1] . '=' . $m[2] . htmlspecialchars($translated, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false) . $m[2];
            },
            $tag,
        ) ?? $tag;
    }

    /** Pecah teks di sekitar angka/tanggal/tanda kurung, terjemahkan hanya potongan yang berupa kalimat. */
    private function translateString(string $text, callable $translate, string $locale): string
    {
        // Pisahkan di sekitar angka/tanggal, pemisah visual (• · |), dan tanda kurung () agar kurung tidak hilang.
        $parts = preg_split('/(\d[\d.,:\/\-]*|\s[•·|–—]\s|[•·\(\)])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $text;
        }

        foreach ($parts as $i => $part) {
            // Indeks ganjil = angka/pemisah/kurung (dipertahankan apa adanya).
            if ($i % 2 === 1 || ! preg_match('/^(\s*)(.*?)(\s*)$/su', $part, $m) || $m[2] === '') {
                continue;
            }

            $core = (string) preg_replace('/\s+/u', ' ', $m[2]);
            if (! isset($this->dictionaries[$locale][$core]) && ! $this->isTranslatable($core)) {
                continue;
            }

            $parts[$i] = $m[1] . $translate($core) . $m[3];
        }

        return implode('', $parts);
    }

    private function isTranslatable(string $text): bool
    {
        if (! preg_match('/\p{L}{2,}/u', $text)) {
            return false;
        }
        if (in_array($text, self::IGNORED, true)) {
            return false;
        }
        // Email, URL, kode (mis. RRMS-XXXX), huruf kapital pendek (singkatan).
        if (preg_match('~^[\w.+-]+@[\w.-]+\.\w+$~u', $text) || preg_match('~^(https?://|www\.|/)~i', $text)) {
            return false;
        }
        if (preg_match('/^[\p{Lu}0-9 _\-\/]+$/u', $text) && mb_strlen($text) <= 4) {
            return false;
        }
        if (preg_match('/^[\p{Lu}][\p{Lu}0-9_\-]*$/u', $text) && preg_match('/[\-_]/', $text)) {
            return false;
        }
        // Tidak ada spasi dan panjang: kemungkinan token/kode.
        if (! str_contains($text, ' ') && mb_strlen($text) > 24) {
            return false;
        }

        return true;
    }

    /** @param list<string> $fragments */
    private function fetchMissing(array $fragments, string $locale): void
    {
        if (Cache::get('auto_translate.down')) {
            return;
        }

        $fragments = array_slice($fragments, 0, (int) config('translation.max_per_request', 120));
        $results = $this->translator->translate($fragments, (string) config('translation.source', 'id'), $locale);

        $added = [];
        foreach ($fragments as $i => $fragment) {
            $translated = $results[$i] ?? null;
            if (is_string($translated) && $translated !== '') {
                $this->dictionaries[$locale][$fragment] = $translated;
                $added[$fragment] = $translated;
            }
        }

        if ($added === []) {
            // Penerjemah tidak dapat dihubungi: jangan coba lagi selama semenit agar halaman tetap cepat.
            Cache::put('auto_translate.down', true, 60);

            return;
        }

        $this->persist($locale, $added);
    }

    /** @param array<string, string> $added */
    private function persist(string $locale, array $added): void
    {
        $path = $this->storePath($locale);
        if (! is_dir(dirname($path))) {
            @mkdir(dirname($path), 0775, true);
        }

        $handle = @fopen($path, 'c+');
        if (! $handle) {
            return;
        }

        try {
            flock($handle, LOCK_EX);
            $current = json_decode((string) stream_get_contents($handle), true);
            $merged = array_merge(is_array($current) ? $current : [], $added);
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function storePath(string $locale): string
    {
        return storage_path('app/translations/' . $locale . '.json');
    }

    private function load(string $locale): void
    {
        if (isset($this->dictionaries[$locale])) {
            return;
        }

        $dictionary = [];
        $known = [];
        $source = (string) config('translation.source', 'id');

        // 1) Pasangan terjemahan dari file lang/{source} -> lang/{locale} yang sudah ada.
        $sourceStrings = $this->flattenLangFiles($source);
        $targetStrings = $this->flattenLangFiles($locale);
        foreach ($targetStrings as $key => $value) {
            $known[$this->normalize($value)] = true;
            if (isset($sourceStrings[$key])) {
                $dictionary[$this->normalize($sourceStrings[$key])] = $value;
            }
        }

        // 2) Terjemahan otomatis yang tersimpan (dan koreksi manual) diutamakan.
        $path = $this->storePath($locale);
        if (is_file($path)) {
            $stored = json_decode((string) file_get_contents($path), true);
            if (is_array($stored)) {
                $dictionary = array_merge($dictionary, $stored);
            }
        }

        $this->dictionaries[$locale] = $dictionary;
        $this->knownTarget[$locale] = $known;
    }

    /** @return array<string, string> */
    private function flattenLangFiles(string $locale): array
    {
        $result = [];
        foreach (glob(lang_path($locale . DIRECTORY_SEPARATOR . '*.php')) ?: [] as $file) {
            $rows = include $file;
            if (is_array($rows)) {
                $this->flatten($rows, basename($file, '.php'), $result);
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $rows @param array<string, string> $out */
    private function flatten(array $rows, string $prefix, array &$out): void
    {
        foreach ($rows as $key => $value) {
            if (is_array($value)) {
                $this->flatten($value, $prefix . '.' . $key, $out);
            } elseif (
                is_string($value)
                && ! str_contains($value, ':')
                && ! str_contains($value, '|')
                && mb_strlen($this->normalize($value)) >= 2
            ) {
                $out[$prefix . '.' . $key] = $value;
            }
        }
    }

    private function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
