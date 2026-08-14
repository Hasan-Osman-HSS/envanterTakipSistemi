<?php
$id = isset($argv[1]) ? intval($argv[1]) : 0;
if ($id <= 0) {
    die("Usage: php sync_snippet.php <id>\n");
}

$mysqli = new mysqli("127.0.0.1", "root", "root", "local", 10006);
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

$dir = __DIR__ . '/snippets_dump';
$files = glob($dir . "/snippet_{$id}_*.php");
if (empty($files)) {
    die("File not found for snippet ID {$id}\n");
}

$filePath = $files[0];
$content = file_get_contents($filePath);
$code = preg_replace('/^\s*<\?php\s*\/\* ID: \d+ \| Name: .*? \*\/\s*/su', '', $content);

$stmt = $mysqli->prepare("UPDATE wp_snippets SET code = ? WHERE id = ?");
$stmt->bind_param("si", $code, $id);
if ($stmt->execute()) {
    echo "SUCCESS: Snippet {$id} synced!\n";
} else {
    echo "ERROR: " . $stmt->error . "\n";
}
