<?php
$mysqli = new mysqli("127.0.0.1", "root", "root", "local", 10005);
$mysqli->set_charset("utf8mb4");

$res = $mysqli->query("SELECT id, name, code FROM wp_snippets WHERE active = 1");
while ($row = $res->fetch_assoc()) {
    $code = $row['code'];
    if (preg_match('/(\*|button|span|a|div)\s*\{[^}]*!important/i', $code, $m) || strpos($code, 'F8F3E6') !== false || strpos($code, '1B1D28 !important') !== false) {
        echo "=== SNIPPET ID {$row['id']} ({$row['name']}) HAS OVERRIDING !IMPORTANT ===\n";
        preg_match_all('/[^{}]*!important[^{}]*;/i', $code, $matches);
        foreach (array_slice($matches[0], 0, 10) as $match) {
            echo "   " . trim($match) . "\n";
        }
        echo "\n";
    }
}
