<?php
// db.php — для PostgreSQL на Render

// Данные берутся из Internal/External DATABASE_URL или параметров БД с Render
$host     = "dpg-d9grrh6pbkes73c77q80-a"; // Вставишь свой Internal или External Host из панели Render
$port     = "5432";
$user     = "sereqa";
$pass     = "UieNKFeX7sMMEpYAE2z99ODEhFYRzeKT";
$dbname   = "lovesity";

$conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$pass}";
$db = pg_connect($conn_string);

if (!$db) {
    die("Ошибка подключения к БД PostgreSQL");
}
?>
