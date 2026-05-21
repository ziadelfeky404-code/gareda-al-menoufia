<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id > 0) {

    $articles_file = DATA_PATH . '/articles.json';
    if (file_exists($articles_file)) {
        $articles = json_decode(file_get_contents($articles_file), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $new_articles = [];
            foreach ($articles as $a) {
                if ($a['id'] != $id) $new_articles[] = $a;
            }
            if (count($new_articles) < count($articles)) {
                $new_articles = array_values($new_articles);
                save_articles($new_articles);
            }
        }
    }
}

$redirect = $_GET['redirect'] ?? '';
if ($redirect && strpos($redirect, 'article-delete.php') === false) {
    $sep = (strpos($redirect, '?') !== false) ? '&' : '?';
    header('Location: ' . $redirect . $sep . 'deleted=1');
} else {
    header('Location: articles.php' . ($id > 0 ? '?deleted=1' : ''));
}
exit;
