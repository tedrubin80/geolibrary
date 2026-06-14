<?php
/**
 * GeoOptimizer Core functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class GeoOptimizer_Core {

    /**
     * Detect visitor geolocation
     */
    public static function get_visitor_location() {
        $ip = self::get_visitor_ip();

        // Use WordPress transient for caching
        $cache_key = 'geoopt_loc_' . md5($ip);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $location = self::lookup_ip($ip);

        if ($location) {
            set_transient($cache_key, $location, HOUR_IN_SECONDS);
        }

        return $location;
    }

    /**
     * Get visitor IP address
     */
    public static function get_visitor_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated list
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * IP geolocation lookup
     */
    private static function lookup_ip($ip) {
        // Placeholder - integrate with geolocation service
        return [
            'ip' => $ip,
            'country' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
        ];
    }

    /**
     * Check if content should be shown for location
     */
    public static function should_show_for_location($rules, $location = null) {
        if (empty($rules)) {
            return true;
        }

        if ($location === null) {
            $location = self::get_visitor_location();
        }

        // Implement rule matching
        return true;
    }
}
