<?php

namespace Symfony\Component\HttpFoundation {
    if (!\function_exists('Symfony\Component\HttpFoundation\request_parse_body')) {
        function request_parse_body(?string $contentType = null): array
        {
            $data = [];
            if ($contentType === null) {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            }
            if (str_contains($contentType, 'application/x-www-form-urlencoded') || str_contains($contentType, 'multipart/form-data')) {
                parse_str(file_get_contents('php://input'), $data);
            }
            return [$data, []];
        }
    }
}

namespace {
    use Illuminate\Foundation\Application;
    use Illuminate\Http\Request;

    define('LARAVEL_START', microtime(true));

    if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
        require $maintenance;
    }

    require __DIR__.'/../vendor/autoload.php';

    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

    $app->handleRequest(Request::capture());
}
