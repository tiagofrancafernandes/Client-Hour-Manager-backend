<?php

$uri = request_uri();

define('CONTENT_PATH', realpath(__DIR__ . '/../www-content'));

$fallbackController = function (string $_uri, bool $onlyRoutes = false) {
    // If $onlyRoutes is 'true', other uris will return 404

    if ($onlyRoutes) {
        return __DIR__ . '/404.php';
    }

    $filePath = CONTENT_PATH . "/{$_uri}.php";

    return is_file($filePath) ? $filePath : __DIR__ . '/404.php';
};

$uri = trim(ltrim($uri, '/'));

$filePath = match ($uri) {
    '', '/', 'home', 'index', 'index.php' => CONTENT_PATH . '/index.php',
    'about', 'about.php' => CONTENT_PATH . '/about.php',
    'latest_log', 'git' => CONTENT_PATH . '/git.php',
    'contact', 'contact.php' => CONTENT_PATH . '/contact.php',
    // 'index', 'index.php' => CONTENT_PATH . '/index.php',

    // default => __DIR__ . '/404.php',
    default => $fallbackController($uri),
};

require $filePath;
