<?php
$mysqli = new mysqli("127.0.0.1", "root", "root", "local", 10005);
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

$res = $mysqli->query("SELECT ID, post_title, post_name, post_content FROM wp_posts WHERE post_type = 'page' AND post_status = 'publish'");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['ID']} | Title: {$row['post_title']} | Slug: {$row['post_name']}\n";
    echo "   Content: " . substr(trim(str_replace("\n", " ", $row['post_content'])), 0, 100) . "\n\n";
}
