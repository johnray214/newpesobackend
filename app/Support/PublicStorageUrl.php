<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class PublicStorageUrl
{
    /**
     * URL for a public-disk path.
     * Uses Laravel's Storage::url() which picks up the correct URL from the default disk (e.g., S3/R2).
     */
    public static function fromRequest(Request $request, ?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $stored = str_replace('\\', '/', trim($stored));

        if (preg_match('#^https?://#i', $stored)) {
            return $stored;
        }

        // Use the default disk (which we set to 's3' for R2)
        try {
            return Storage::url($stored);
        } catch (\Exception $e) {
            // Fallback to local-style if anything fails
            return $request->getSchemeAndHttpHost().'/storage/'.ltrim($stored, '/');
        }
    }
}
