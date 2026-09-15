<?php

namespace App\Helpers;

class UrlHelper
{
    /**
     * Format any path or legacy absolute URL (with old domain/IP) into a dynamic current-domain URL.
     *
     * @param string|null $value
     * @return string|null
     */
    public static function formatUrl(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (str_starts_with($value, 'data:image')) {
            return $value;
        }

        // Match /uploads/... or /storage/... in the string even if prepended with old IP/domain
        if (preg_match('#/(uploads|storage)/.+$#i', $value, $matches)) {
            return url(ltrim($matches[0], '/'));
        }

        if (str_starts_with($value, 'uploads/') || str_starts_with($value, 'storage/')) {
            return url($value);
        }

        // If it is a valid full URL to an external service (not local uploads/storage)
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return url(ltrim($value, '/'));
    }

    /**
     * Convert any full URL or path to a clean relative path (e.g., "uploads/schools/2/image.png") for DB storage.
     *
     * @param string|null $value
     * @return string|null
     */
    public static function cleanRelativePath(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (str_starts_with($value, 'data:image')) {
            return $value;
        }

        if (preg_match('#/(uploads|storage)/.+$#i', $value, $matches)) {
            return ltrim($matches[0], '/');
        }

        $path = parse_url($value, PHP_URL_PATH);
        if ($path) {
            return ltrim($path, '/');
        }

        return ltrim($value, '/');
    }
}
