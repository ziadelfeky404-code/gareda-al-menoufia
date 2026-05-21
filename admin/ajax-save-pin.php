<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    exit;
}

$position = trim($_POST['position'] ?? '');
$articleId = (int)($_POST['article_id'] ?? 0);

if (!$position || !$articleId) {
    http_response_code(400);
    exit;
}

// Verify article exists
$article = get_article($articleId);
if (!$article) {
    http_response_code(404);
    exit;
}

$settings_file = DATA_PATH . '/settings.json';
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) $settings = [];
}

if (strpos($position, 'hero_slide_') === 0) {
    $pins = $settings['hero_slide_pins'] ?? [24, 7, 2, 3, 4, 22, 23];
    $idx = (int)substr($position, 11);
    if ($idx >= 0 && $idx < count($pins)) {
        $pins[$idx] = $articleId;
        $settings['hero_slide_pins'] = $pins;
    }
} elseif (strpos($position, 'hero_') === 0) {
    $pins = $settings['hero_pins'] ?? [0, 1, 5, 6, 24, 7];
    $idx = (int)substr($position, 5);
    if ($idx >= 0 && $idx < 6) {
        $pins[$idx] = $articleId;
        $settings['hero_pins'] = $pins;
    }
}

if (save_settings($settings)) {
    echo 'OK';
} else {
    http_response_code(500);
}
