<?php

declare(strict_types=1);

namespace App\Shared\Support;

final class UrlNormalizer
{
    /**
     * Normalize a URL to a canonical form.
     *   - non-string, null, or empty-after-trim → return input unchanged (caller decides validation)
     *   - starts with http:// or https:// (any case) → lowercase the scheme only, preserve rest
     *   - starts with // (protocol-relative) → prepend 'https:'
     *   - starts with any other scheme (ftp:, javascript:, data:, mailto:) → leave as-is
     *   - otherwise (schemeless host/path) → prepend 'https://'
     *
     * Idempotent: normalize(normalize(x)) === normalize(x).
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);

        if ($trimmed === '') {
            return $raw;
        }

        $lower = strtolower($trimmed);

        if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')) {
            $schemeEnd = (int) strpos($trimmed, '://');

            return strtolower(substr($trimmed, 0, $schemeEnd)).substr($trimmed, $schemeEnd);
        }

        if (str_starts_with($trimmed, '//')) {
            return 'https:'.$trimmed;
        }

        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $trimmed)) {
            return $trimmed;
        }

        return 'https://'.$trimmed;
    }
}
