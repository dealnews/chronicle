<?php

use DealNews\Chronicle\Controller\Admin;
use DealNews\Chronicle\Controller\Auth;
use DealNews\Chronicle\Controller\History;
use DealNews\Chronicle\Controller\NotFound;
use DealNews\Chronicle\Controller\Webhook;
use PageMill\Router\Router;

// When running under the PHP built-in server, serve static files directly
// rather than routing them through index.php.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

// Session registration happens in the controllers that need it
// (AbstractAuthenticated and Auth) to avoid unnecessary DB reads
// on webhook requests.

$router = new Router([
    // Webhook ingestion — API-key authenticated, no session required
    [
        'type'    => 'regex',
        'pattern' => '#^/webhook/([^/]+)/([^/]+)$#',
        'method'  => 'POST',
        'tokens'  => ['source', 'type'],
        'action'  => Webhook::class,
    ],

    // Authentication — login form (GET renders, POST authenticates)
    [
        'type'    => 'exact',
        'pattern' => '/auth/login',
        'method'  => 'GET',
        'action'  => Auth::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/auth/login',
        'method'  => 'POST',
        'action'  => Auth::class,
    ],

    // First-run setup — creates the initial admin account
    [
        'type'    => 'exact',
        'pattern' => '/auth/setup',
        'method'  => 'POST',
        'action'  => Auth::class,
    ],

    // Google OAuth callback
    [
        'type'    => 'exact',
        'pattern' => '/auth/callback',
        'method'  => 'GET',
        'action'  => Auth::class,
    ],

    // Logout
    [
        'type'    => 'exact',
        'pattern' => '/auth/logout',
        'method'  => 'GET',
        'action'  => Auth::class,
    ],

    // Admin UI — sources, types, API keys (session required)
    [
        'type'    => 'starts_with',
        'pattern' => '/admin',
        'action'  => Admin::class,
    ],

    // History UI — object history browser (session required)
    [
        'type'    => 'exact',
        'pattern' => '/',
        'method'  => 'GET',
        'action'  => History::class,
    ],
    [
        'type'    => 'regex',
        'pattern' => '#^/([^/]+)/([^/]+)/([^/]+)$#',
        'method'  => 'GET',
        'tokens'  => ['source', 'type', 'object_id'],
        'action'  => History::class,
    ],
    [
        'type'    => 'regex',
        'pattern' => '#^/([^/]+)/([^/]+)$#',
        'method'  => 'GET',
        'tokens'  => ['source', 'type'],
        'action'  => History::class,
    ],
    [
        'type'    => 'regex',
        'pattern' => '#^/([^/]+)$#',
        'method'  => 'GET',
        'tokens'  => ['source'],
        'action'  => History::class,
    ],

    // Catch-all 404
    [
        'type'   => 'default',
        'action' => NotFound::class,
    ],
]);

$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route        = $router->match($request_path);

if (empty($route)) {
    http_response_code(404);
    exit;
}

$inputs     = $route['tokens'] ?? [];
$controller = new $route['action']($request_path, $inputs);
$controller->handleRequest();
