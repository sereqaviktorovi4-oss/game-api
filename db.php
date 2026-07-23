<?php
// db.php
$host = "sql206.infinityfree.com";
$user = "if0_38379031"; 
$pass = "sx2cuTTkpnJ";
$dbname = "if0_38379031_lovesity";

$db = new mysqli($host, $user, $pass, $dbname);

if ($db->connect_error) {
    die("Ошибка подключения к БД: " . $db->connect_error);
}

$db->set_charset("utf8mb4");
?>
