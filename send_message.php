<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { 
    exit("NOT_AUTH"); 
}

$user_id = (int)$_SESSION['user_id'];

// Одним запросом берем и ник, и баланс
$res_u = $db->query("SELECT username, citymoney FROM users WHERE id = $user_id");
$u_data = $res_u->fetch_assoc();

if (!$u_data) {
    exit("USER_NOT_FOUND");
}

$username = $db->real_escape_string($u_data['username']);
$message = isset($_POST['message']) ? $db->real_escape_string(trim($_POST['message'])) : '';
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
    $db->query("UPDATE users SET citymoney = citymoney - $price WHERE id = $user_id");
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

if ($db->query($sql)) {
    echo "SUCCESS";
} else {
    // Если здесь выдает SQL_ERROR, значит в таблице 'chat' не хватает колонки 'is_vip' или 'user_id'
    echo "SQL_ERROR: " . $db->error;
}
?>
