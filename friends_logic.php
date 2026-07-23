<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    die("Ошибка авторизации.");
}

$my_id = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$target_id = (int)($_GET['id'] ?? 0);

if ($target_id <= 0 || $target_id === $my_id) {
    pg_close($db);
    die("Некорректный ID пользователя.");
}

// 1. ДОБАВЛЕНИЕ В ДРУЗЬЯ
if ($action === 'add') {
    // Проверяем, нет ли уже заявки или дружбы
    $check = pg_query($db, "SELECT id, status FROM friends WHERE (user_id=$my_id AND friend_id=$target_id) OR (user_id=$target_id AND friend_id=$my_id)");
    
    if ($check && pg_num_rows($check) == 0) {
        // Создаем заявку в таблицу friends (со статусом pending)
        pg_query($db, "INSERT INTO friends (user_id, friend_id, status) VALUES ($my_id, $target_id, 'pending')");
        
        // Создаем системное уведомление для получателя
        $username = isset($_SESSION['username']) ? pg_escape_string($db, $_SESSION['username']) : 'Житель';
        $msg = "Житель " . $username . " хочет добавиться к вам в друзья!";
        pg_query($db, "INSERT INTO notifications (user_id, message) VALUES ($target_id, '$msg')");
        
        echo "Заявка отправлена!";
    } else {
        echo "Заявка уже существует или вы уже друзья.";
    }
}

// 2. ПОДТВЕРЖДЕНИЕ ЗАЯВКИ
if ($action === 'accept') {
    // Обновляем статус на 'accepted'
    pg_query($db, "UPDATE friends SET status='accepted' WHERE user_id=$target_id AND friend_id=$my_id");
    
    // Уведомляем того, кто кидал заявку
    $username = isset($_SESSION['username']) ? pg_escape_string($db, $_SESSION['username']) : 'Житель';
    $msg = "Житель " . $username . " принял вашу заявку в друзья!";
    pg_query($db, "INSERT INTO notifications (user_id, message) VALUES ($target_id, '$msg')");
    
    pg_close($db);
    header("Location: friends_online.php?msg=success");
    exit;
}

// 3. УДАЛЕНИЕ ИЗ ДРУЗЕЙ / ОТКЛОНЕНИЕ
if ($action === 'delete') {
    pg_query($db, "DELETE FROM friends WHERE (user_id=$my_id AND friend_id=$target_id) OR (user_id=$target_id AND friend_id=$my_id)");
    echo "Пользователь удален из списка.";
    pg_close($db);
    header("Location: friends_online.php");
    exit;
}

pg_close($db);
?>
