<?php

declare(strict_types=1);

/**
 * Built-in PHP server router for Railway / local full-stack serving.
 * Document root: public/
 *
 *   php -S 0.0.0.0:$PORT -t public public/router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? $uri : '/';
$uri = rawurldecode($uri);

// API: strip /api prefix and hand off to the REST front controller.
if ($uri === '/api' || strpos($uri, '/api/') === 0) {
    $apiPath = substr($uri, strlen('/api'));
    if ($apiPath === '' || $apiPath === false) {
        $apiPath = '/';
    }
    if ($apiPath[0] !== '/') {
        $apiPath = '/' . $apiPath;
    }

    $_SERVER['GEO_API_PATH'] = $apiPath;
    require __DIR__ . '/api/index.php';
    return true;
}

$file = __DIR__ . $uri;

// Serve existing static files as-is.
if ($uri !== '/' && is_file($file)) {
    return false;
}

// Directory indexes (e.g. /dashboard/).
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.html';
    if (is_file($index)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($index);
        return true;
    }
}

// Fallback for the marketing homepage.
if ($uri === '/' || $uri === '') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/index.html');
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo "Not Found\n";
return true;
