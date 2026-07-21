<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use GEOOptimizer\API\RESTController;

$controller = new RESTController();

$headers = [];
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $key => $value) {
        $headers[strtolower((string) $key)] = (string) $value;
    }
}

$path = $_SERVER['GEO_API_PATH'] ?? null;
if (!is_string($path) || $path === '') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = is_string($path) ? $path : '/';

    // When served under /api from the public document root, strip the prefix.
    if ($path === '/api' || strpos($path, '/api/') === 0) {
        $path = substr($path, strlen('/api'));
        if ($path === '' || $path === false) {
            $path = '/';
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
    }
}

$body = file_get_contents('php://input');
$response = $controller->handle(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $path,
    $headers,
    $body === false ? null : $body,
    $_GET
);

http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header($name . ': ' . $value);
}

echo $response['body'];
