<?php
// db.php — для PostgreSQL на Render с сохранением сессий в БД

// 1. Сначала подключаемся к базе данных, чтобы соединение $db было доступно для обработчика сессий
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

// 2. Настраиваем сохранение сессий через пользовательский обработчик в PostgreSQL
ini_set('session.save_handler', 'user');

session_set_save_handler(
    function($path, $name) { 
        return true; 
    },
    function() { 
        return true; 
    },
    function($id) {
        global $db;
        $id_escaped = pg_escape_string($db, $id);
        $res = pg_query($db, "SELECT data FROM sessions WHERE id = '$id_escaped'");
        if ($res && $row = pg_fetch_assoc($res)) {
            return $row['data'];
        }
        return '';
    },
    function($id, $data) {
        global $db;
        $id_escaped = pg_escape_string($db, $id);
        $data_escaped = pg_escape_string($db, $data);
        $expires = time() + 86400; // Время жизни сессии: 1 день
        
        $query = "INSERT INTO sessions (id, data, expires) VALUES ('$id_escaped', '$data_escaped', $expires) 
                  ON CONFLICT (id) DO UPDATE SET data = '$data_escaped', expires = $expires";
        pg_query($db, $query);
        return true;
    },
    function($id) {
        global $db;
        $id_escaped = pg_escape_string($db, $id);
        pg_query($db, "DELETE FROM sessions WHERE id = '$id_escaped'");
        return true;
    },
    function($maxlifetime) {
        global $db;
        $expires = time();
        pg_query($db, "DELETE FROM sessions WHERE expires < $expires");
        return true;
    }
);

// 3. Безопасный запуск сессии для всего проекта
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
