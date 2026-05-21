<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    exit;
}

$position = trim($_POST['position'] ?? '');
$image = trim($_POST['image'] ?? '');
$link = trim($_POST['link'] ?? '');
$width = trim($_POST['width'] ?? '');
$height = trim($_POST['height'] ?? '');
$container = trim($_POST['container'] ?? '');
$offsetX = trim($_POST['offset_x'] ?? '');
$offsetY = trim($_POST['offset_y'] ?? '');

if (!$position) {
    http_response_code(400);
    exit;
}

$settings_file = DATA_PATH . '/settings.json';
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) $settings = [];
}

$ads = $settings['ads'] ?? [];

if ($image !== '' || $link !== '') {
    $ads[$position] = $ads[$position] ?? ['type' => 'image'];
    $ads[$position]['type'] = 'image';
    if ($image !== '') $ads[$position]['image'] = $image;
    if ($link !== '') $ads[$position]['link'] = $link;
}
if ($width !== '' || $height !== '') {
    $ads[$position] = $ads[$position] ?? ['type' => 'image'];
    if ($width !== '') $ads[$position]['width'] = (int)$width;
    if ($height !== '') $ads[$position]['height'] = (int)$height;
}
if ($offsetX !== '') {
    $ads[$position] = $ads[$position] ?? ['type' => 'image'];
    $ads[$position]['offset_x'] = (int)$offsetX;
}
if ($offsetY !== '') {
    $ads[$position] = $ads[$position] ?? ['type' => 'image'];
    $ads[$position]['offset_y'] = (int)$offsetY;
}
if ($container !== '') {
    $settings['ad_container'] = $container;
}

$settings['ads'] = $ads;

if (save_settings($settings)) {
    echo 'OK';
} else {
    http_response_code(500);
}
