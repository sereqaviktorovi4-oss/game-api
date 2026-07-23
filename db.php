<?php
// db.php
$host = "dpg-d9grrh6pbkes73c77q80-a";
$user = "sereqa"; 
$pass = "UieNKFeX7sMMEpYAE2z99ODEhFYRzeKT";
$dbname = "lovesity";

$db = new mysqli($host, $user, $pass, $dbname);

if ($db->connect_error) {
    die("Ошибка подключения к БД: " . $db->connect_error);
}

$db->set_charset("utf8mb4");
?>
