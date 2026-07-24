<?php
// db.php — исправленная версия для PostgreSQL и стабильных сессий на Render

// 1. Указываем общий системный путь для файлов сессий, чтобы они не терялись
$session_path = sys_get_temp_dir();
session_save_path($session_path);

// 2. Настраиваем параметры куки сессии для домена Render
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_path', '/');
ini_set('session.use_strict_mode', '1');

// Запускаем сессию безопасно, если она еще не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Подключение к базе данных PostgreSQL
$host     = "dpg-d9grrh6pbkes73c77q80-a"; 
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
