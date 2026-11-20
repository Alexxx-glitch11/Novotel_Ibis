<?php

namespace App\Http\Middleware;

use App\Services\AutoTranslator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menerjemahkan halaman HTML otomatis saat bahasa bukan bahasa sumber (Indonesia).
 * Lihat config/translation.php.
 */
class AutoTranslate
{
    public function __construct(private readonly AutoTranslator $translator)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $locale = app()->getLocale();

        $status = $response instanceof LaravelResponse ? $response->getStatusCode() : 0;

        if (! $this->translator->enabledFor($locale)
            || ! $response instanceof LaravelResponse
            || ($status >= 300 && $status < 400)
            || ! str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === ''
            || (stripos($content, '<html') === false && stripos($content, '<body') === false && stripos($content, '<div') === false)) {
            return $response;
        }

        $response->setContent($this->translator->translateHtml($content, $locale));

        return $response;
    }
}
