<?php
$mysqli = new mysqli("127.0.0.1", "root", "root", "local", 10005);
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

// FIX SNIPPET 42
$res = $mysqli->query("SELECT code FROM wp_snippets WHERE id = 42");
$code42 = $res->fetch_assoc()['code'];

$code42 = str_replace("<?php\n    \n    <div id=\"heshelCihazDetayModal\"", "?>\n    <div id=\"heshelCihazDetayModal\"", $code42);
$code42 = str_replace("<?php\n    <div id=\"heshelCihazDetayModal\"", "?>\n    <div id=\"heshelCihazDetayModal\"", $code42);

$stmt42 = $mysqli->prepare("UPDATE wp_snippets SET code = ? WHERE id = 42");
$stmt42->bind_param("s", $code42);
$stmt42->execute();
echo "Snippet 42 tags fixed!\n";

// FIX SNIPPET 43
$res = $mysqli->query("SELECT code FROM wp_snippets WHERE id = 43");
$code43 = $res->fetch_assoc()['code'];

$code43 = str_replace("<?php\n    \n    <script>", "?>\n    <script>", $code43);
$code43 = str_replace("<?php\n    <script>", "?>\n    <script>", $code43);

$stmt43 = $mysqli->prepare("UPDATE wp_snippets SET code = ? WHERE id = 43");
$stmt43->bind_param("s", $code43);
$stmt43->execute();
echo "Snippet 43 tags fixed!\n";
