<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';
session_start();

// 1. Простая проверка авторизации
if (!isset($_SESSION['user_id'])) {
    die("AUTH_ERROR");
}

$sender_id = (int)$_SESSION['user_id'];
$to_id = isset($_POST['to_id']) ? (int)$_POST['to_id'] : 0;
$gift_icon = isset($_POST['icon']) ? $db->real_escape_string($_POST['icon']) : '';
$price = 30;

if ($to_id <= 0 || empty($gift_icon)) {
    die("INVALID_DATA");
}

// 2. Проверяем баланс (используем колонку citymoney, как на скрине)
$res = $db->query("SELECT citymoney FROM users WHERE id = $sender_id");
if (!$res) die("DATABASE_ERROR_USERS: " . $db->error);

$user_data = $res->fetch_assoc();

if (!$user_data || (int)$user_data['citymoney'] < $price) {
    die("LOW_MONEY");
}

// 3. Снимаем деньги (используем citymoney)
$update = $db->query("UPDATE users SET citymoney = citymoney - $price WHERE id = $sender_id");
if (!$update) die("DATABASE_ERROR_UPDATE: " . $db->error);

// 4. Записываем подарок в таблицу user_gifts
$insert = $db->query("INSERT INTO user_gifts (sender_id, receiver_id, gift_icon) VALUES ($sender_id, $to_id, '$gift_icon')");

if ($insert) {
    echo "SUCCESS";
} else {
    // Если подарок не записался, вернем деньги (опционально) и выведем ошибку
    $db->query("UPDATE users SET citymoney = citymoney + $price WHERE id = $sender_id");
    echo "DB_INSERT_ERROR: " . $db->error;
}
?>
