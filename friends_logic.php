<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Ошибка авторизации.");
}

$my_id = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$target_id = (int)($_GET['id'] ?? 0);

if ($target_id <= 0 || $target_id === $my_id) {
    die("Некорректный ID пользователя.");
}

// 1. ДОБАВЛЕНИЕ В ДРУЗЬЯ
if ($action === 'add') {
    // Проверяем, нет ли уже заявки или дружбы
    $check = $db->query("SELECT id, status FROM friends WHERE (user_id=$my_id AND friend_id=$target_id) OR (user_id=$target_id AND friend_id=$my_id)");
    
    if ($check->num_rows == 0) {
        // Создаем заявку в таблицу friends (со статусом pending)
        $db->query("INSERT INTO friends (user_id, friend_id, status) VALUES ($my_id, $target_id, 'pending')");
        
        // Создаем системное уведомление для получателя
        $msg = "Житель " . $_SESSION['username'] . " хочет добавиться к вам в друзья!";
        $db->query("INSERT INTO notifications (user_id, message) VALUES ($target_id, '$msg')");
        
        echo "Заявка отправлена!";
    } else {
        echo "Заявка уже существует или вы уже друзья.";
    }
}

// 2. ПОДТВЕРЖДЕНИЕ ЗАЯВКИ
if ($action === 'accept') {
    // Обновляем статус на 'accepted'
    $db->query("UPDATE friends SET status='accepted' WHERE user_id=$target_id AND friend_id=$my_id");
    
    // Уведомляем того, кто кидал заявку
    $msg = "Житель " . $_SESSION['username'] . " принял вашу заявку в друзья!";
    $db->query("INSERT INTO notifications (user_id, message) VALUES ($target_id, '$msg')");
    
    header("Location: friends_online.php?msg=success");
}

// 3. УДАЛЕНИЕ ИЗ ДРУЗЕЙ / ОТКЛОНЕНИЕ
if ($action === 'delete') {
    $db->query("DELETE FROM friends WHERE (user_id=$my_id AND friend_id=$target_id) OR (user_id=$target_id AND friend_id=$my_id)");
    echo "Пользователь удален из списка.";
    header("Location: friends_online.php");
}
?>
