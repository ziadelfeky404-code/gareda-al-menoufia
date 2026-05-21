<?php
require 'includes/config.php';

$arts = get_articles_by_section("الفن والمسابقات");
for ($i = 0; $i < min(5, count($arts)); $i++) {
    $a = $arts[$i];
    echo "ID: {$a["id"]}\n";
    echo "Title: {$a["title"]}\n";
    echo "Image: '{$a["image"]}'\n";
    echo "Author: '{$a["author"]}'\n";
    echo "Paragraphs:\n";
    foreach ($a["paragraphs"] as $j => $p) {
        echo "  P$j: " . mb_substr($p, 0, 120) . "\n";
    }
    echo "\n---\n";
}

// Check last 3
echo "\n===== LAST 3 =====\n";
$last = array_slice($arts, -3);
foreach ($last as $a) {
    echo "ID: {$a["id"]}\n";
    echo "Title: {$a["title"]}\n";
    echo "Image: '{$a["image"]}'\n";
    echo "\n";
}
