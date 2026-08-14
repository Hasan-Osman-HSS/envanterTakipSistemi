<?php
$mysqli = new mysqli("127.0.0.1", "root", "root", "local", 10005);
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

$res = $mysqli->query("SELECT id, name, active, code FROM wp_snippets WHERE active = 1");
while ($row = $res->fetch_assoc()) {
    if (preg_match('/yazd[ıi]r|print|zimmet|cihaz|icon|img|svg|logo/ui', $row['code'])) {
        echo "=== SNIPPET ID: {$row['id']} | NAME: {$row['name']} ===\n";
        // Search specifically for print/yazdir matches
        preg_match_all('/.{0,50}(?:yazd[ıi]r|print|zimmet|logo|img|svg).{0,50}/ui', $row['code'], $matches);
        foreach ($matches[0] as $m) {
            echo "   MATCH: " . trim(str_replace("\n", " ", $m)) . "\n";
        }
    }
}
