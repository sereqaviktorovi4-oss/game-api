<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$my_id = $_SESSION['user_id'] ?? 0;

// Сравниваем числовой timestamp (текущее время минус 4 секунды)
$time_limit = time() - 4;
$res = pg_query($db, "SELECT username FROM users WHERE is_typing_at > $time_limit AND id != $my_id LIMIT 1");

if ($res && pg_num_rows($res) > 0) {
    $row = pg_fetch_assoc($res);
    echo htmlspecialchars($row['username']);
} else {
    echo ""; // Никто не пишет
}
?>
