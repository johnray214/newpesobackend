<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PublicStorageUrl
{
    /**
     * URL for a public-disk path (served under /storage/...).
     * Uses the incoming request host so links match the API (e.g. :8000) when APP_URL is wrong.
     */
    public static function fromRequest(Request $request, ?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $stored = str_replace('\\', '/', trim($stored));

        if (preg_match('#^https?://#i', $stored)) {
            $path = parse_url($stored, PHP_URL_PATH);
            if (is_string($path) && str_starts_with($path, '/storage/')) {
                return $request->getSchemeAndHttpHost().$path;
            }

            return $stored;
        }

        return $request->getSchemeAndHttpHost().'/storage/'.ltrim($stored, '/');
    }
}
