<?php 
include 'db.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) { 
    exit("NOT_AUTH"); 
}

$user_id = (int)$_SESSION['user_id'];

// Одним запросом берем и ник, и баланс через PostgreSQL
$res_u = pg_query($db, "SELECT username, citymoney FROM users WHERE id = $user_id");
$u_data = ($res_u) ? pg_fetch_assoc($res_u) : null;

if (!$u_data) {
    exit("USER_NOT_FOUND");
}

$username = pg_escape_string($db, $u_data['username']);
$message = isset($_POST['message']) ? pg_escape_string($db, trim($_POST['message'])) : '';
$is_vip = (isset($_POST['is_vip']) && $_POST['is_vip'] == '1') ? 1 : 0;

if (empty($message)) { 
    exit("EMPTY_MSG"); 
}

// Если сообщение VIP — проверяем и списываем 10 CM
if ($is_vip === 1) {
    $price = 10;
    if ($u_data['citymoney'] < $price) {
        exit("ERROR_MONEY"); 
    }
    pg_query($db, "UPDATE users SET citymoney = citymoney - $price WHERE id = $user_id");
}

// ПРОВЕРКА: Куда отправляем?
if (isset($_POST['receiver_id']) && (int)$_POST['receiver_id'] > 0) {
    // 1. ЛИЧНОЕ СООБЩЕНИЕ
    $receiver_id = (int)$_POST['receiver_id'];
    $sql = "INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at, is_vip) 
            VALUES ($user_id, $receiver_id, '$message', 0, NOW(), $is_vip)";
} else {
    // 2. ОБЩИЙ ЧАТ
    // Добавляем user_id, чтобы потом подтягивать аватарку в чате
    $sql = "INSERT INTO chat (user_id, username, message, is_vip) VALUES ($user_id, '$username', '$message', $is_vip)";
}

$result = pg_query($db, $sql);
if ($result) {
    echo "SUCCESS";
} else {
    // Вывод ошибки PostgreSQL, если что-то пойдет не так
    echo "SQL_ERROR: " . pg_last_error($db);
}
?>
