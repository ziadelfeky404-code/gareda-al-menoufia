<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');
if ($uri === '') $uri = '/';

chdir(__DIR__ . '/..');

$path = ltrim($uri, '/');

if ($path === '') $path = 'index.php';

if (strpos($path, 'admin/') === 0) {
    $adminFile = $path;
    if ($adminFile === 'admin' || $adminFile === 'admin/') $adminFile = 'admin/index.php';
    $fullPath = __DIR__ . '/../' . $adminFile;
    if (file_exists($fullPath)) {
        require $fullPath;
        exit;
    }
} elseif (file_exists(__DIR__ . '/../' . $path)) {
    require __DIR__ . '/../' . $path;
    exit;
} elseif (substr($path, -4) !== '.php') {
    $phpPath = $path . '.php';
    if (file_exists(__DIR__ . '/../' . $phpPath)) {
        require __DIR__ . '/../' . $phpPath;
        exit;
    }
}

http_response_code(404);
require __DIR__ . '/../404.php';
