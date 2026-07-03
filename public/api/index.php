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

$body = file_get_contents('php://input');
$response = $controller->handle(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/',
    $headers,
    $body === false ? null : $body,
    $_GET
);

http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header($name . ': ' . $value);
}

echo $response['body'];
