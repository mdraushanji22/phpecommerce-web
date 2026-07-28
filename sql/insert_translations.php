<?php
require_once __DIR__ . '/../config/database.php';

$languages = [
    'en' => __DIR__ . '/../lang/en.php',
    'hi' => __DIR__ . '/../lang/hi.php',
];

$conn = getDB();
$totalInserted = 0;

foreach ($languages as $langCode => $langFile) {
    if (!file_exists($langFile)) {
        echo "Language file not found: $langFile\n";
        continue;
    }

    $translations = require $langFile;
    if (!is_array($translations)) {
        echo "Invalid translation file: $langFile\n";
        continue;
    }

    $count = 0;
    $batches = array_chunk($translations, 50, true);

    foreach ($batches as $batch) {
        $placeholders = [];
        $values = [];
        foreach ($batch as $key => $value) {
            $placeholders[] = "(?, ?, ?)";
            $values[] = $langCode;
            $values[] = $key;
            $values[] = $value;
        }

        $sql = "INSERT IGNORE INTO site_translations (lang_code, translation_key, translation_value) VALUES " . implode(', ', $placeholders);
        $stmt = $conn->prepare($sql);
        $stmt->execute($values);
        $count += $stmt->rowCount();
    }

    $totalInserted += $count;
    echo "[$langCode] Inserted $count translation(s).\n";
}

echo "Done. Total inserted: $totalInserted translation(s).\n";
