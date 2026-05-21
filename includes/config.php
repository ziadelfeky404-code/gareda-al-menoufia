<?php
@ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);
// Polyfill for mb_* functions when mbstring extension is not available
// Uses iconv which is available in this PHP installation
if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = null, $encoding = 'UTF-8') {
        if ($length === null) return iconv_substr($str, $start, iconv_strlen($str, $encoding), $encoding);
        return iconv_substr($str, $start, $length, $encoding);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = 'UTF-8') {
        return iconv_strlen($str, $encoding);
    }
}
if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($str, $to, $from = 'UTF-8') {
        return iconv($from, $to, $str);
    }
}
// Site configuration
define('SITE_NAME', 'جامعة المنوفية');
define('SITE_URL', '');
define('DATA_PATH', __DIR__ . '/../data');
define('ADMIN_PATH', __DIR__ . '/../admin');

// Load settings
$settings_file = DATA_PATH . '/settings.json';
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
}

// Load articles
$articles_file = DATA_PATH . '/articles.json';
$articles = [];
if (file_exists($articles_file)) {
    $articles = json_decode(file_get_contents($articles_file), true);
}

// Helper functions
function get_setting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? $settings[$key] : $default;
}

function get_article($id) {
    global $articles;
    foreach ($articles as $a) {
        if ($a['id'] == $id) return $a;
    }
    return null;
}

function get_latest_article() {
    global $articles;
    $last = end($articles);
    return $last ?: null;
}

function get_articles_by_section($section) {
    global $articles;
    $result = [];
    foreach ($articles as $a) {
        if ($a['section'] === $section) $result[] = $a;
    }
    return $result;
}

function get_articles_by_tag($tag) {
    global $articles;
    $result = [];
    foreach ($articles as $a) {
        $tags = is_array($a['tags']) ? $a['tags'] : [];
        if (in_array($tag, $tags)) $result[] = $a;
    }
    return $result;
}

function get_next_id() {
    global $articles;
    $max = 0;
    foreach ($articles as $a) {
        if ($a['id'] > $max) $max = $a['id'];
    }
    return $max + 1;
}

function save_articles($data) {
    $file = $GLOBALS['articles_file'];
    return file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function save_settings($data) {
    global $settings_file;
    return file_put_contents($settings_file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function section_file($section_name) {
    $map = [
        'أخبار المنوفية' => 'اخبار المنوفية',
        'منشآت الجامعة' => 'منشات الجامعه',
        'ندوات ومؤتمرات' => 'ندوات ومؤتمرات',
        'تكريم ومسابقات' => 'تكريم ومسابقات',
        'الفن والمسابقات' => 'صفحه الفن والمسابقات اخير'
    ];
    return isset($map[$section_name]) ? $map[$section_name] : '';
}

function section_slug($section_name) {
    $map = [
        'أخبار المنوفية' => 'akhbar',
        'منشآت الجامعة' => 'monshat',
        'ندوات ومؤتمرات' => 'nadawat',
        'تكريم ومسابقات' => 'takreem',
        'الفن والمسابقات' => 'fann',
        'رياضة ومسابقات' => 'ryada',
        'قيادات جامعية' => 'qiyadat',
        'تقارير' => 'takarer'
    ];
    return isset($map[$section_name]) ? $map[$section_name] : '';
}

function get_sections() {
    $defaults = [
        ['name' => 'أخبار المنوفية', 'slug' => 'akhbar', 'file' => 'اخبار المنوفية.htm'],
        ['name' => 'منشآت الجامعة', 'slug' => 'monshat', 'file' => 'منشات الجامعه.htm'],
        ['name' => 'ندوات ومؤتمرات', 'slug' => 'nadawat', 'file' => 'ندوات ومؤتمرات.htm'],
        ['name' => 'تكريم ومسابقات', 'slug' => 'takreem', 'file' => 'تكريم ومسابقات.htm'],
        ['name' => 'الفن والمسابقات', 'slug' => 'fann', 'file' => 'صفحه الفن والمسابقات اخير.htm'],
        ['name' => 'رياضة ومسابقات', 'slug' => 'ryada', 'file' => ''],
        ['name' => 'قيادات جامعية', 'slug' => 'qiyadat', 'file' => ''],
        ['name' => 'تقارير', 'slug' => 'takarer', 'file' => ''],
    ];
    $saved = get_setting('sections', []);
    if (empty($saved)) {
        global $settings, $settings_file;
        $settings['sections'] = $defaults;
        file_put_contents($settings_file, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $defaults;
    }
    return $saved;
}

function save_sections($data) {
    global $settings, $settings_file;
    $settings['sections'] = $data;
    return file_put_contents($settings_file, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
?>
