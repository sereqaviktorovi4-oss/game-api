<?php
include 'db.php';
session_start();
$my_id = $_SESSION['user_id'] ?? 0;

// Ищем тех, кто обновил статус за последние 4 секунды
$time_limit = time() - 4;
$res = $db->query("SELECT username FROM users WHERE is_typing_at > $time_limit AND id != $my_id LIMIT 1");

if ($row = $res->fetch_assoc()) {
    echo htmlspecialchars($row['username']);
} else {
    echo ""; // Никто не пишет
}
?>

