<?php
$mysqli = new mysqli("127.0.0.1", "root", "root", "local", 10005);
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

$dir = __DIR__ . '/snippets_dump';
if (!is_dir($dir)) {
    mkdir($dir);
}

$res = $mysqli->query("SELECT id, name, active, code FROM wp_snippets WHERE active = 1");
while ($row = $res->fetch_assoc()) {
    $filename = $dir . '/snippet_' . $row['id'] . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $row['name']) . '.php';
    file_put_contents($filename, "<?php\n/* ID: {$row['id']} | Name: {$row['name']} */\n\n" . $row['code']);
    echo "Saved: snippet_{$row['id']}\n";
}
