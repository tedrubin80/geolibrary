<?php

declare(strict_types=1);

namespace GEOOptimizer\Http;

/**
 * Validates outbound URLs to reduce SSRF risk for webhooks and callbacks.
 */
final class UrlValidator
{
    /**
     * Allow HTTPS webhooks to public hosts only.
     */
    public static function isAllowedWebhookUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if ($scheme !== 'https') {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');
        if ($host === '' || self::isBlockedHost($host)) {
            return false;
        }

        return self::resolvesToPublicIp($host);
    }

    private static function isBlockedHost(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return !self::isPublicIp($host);
        }

        return false;
    }

    private static function resolvesToPublicIp(string $host): bool
    {
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if ($records === false || $records === []) {
            $fallback = gethostbyname($host);
            if ($fallback === $host) {
                return false;
            }

            return self::isPublicIp($fallback);
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip !== null && !self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
