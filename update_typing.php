<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['user_id']) && isset($_GET['status'])) {
    $uid = (int)$_SESSION['user_id'];
    $status = (int)$_GET['status'];
    // Обновляем время печати
    $time = ($status == 1) ? time() : 0;
    
    pg_query($db, "UPDATE users SET is_typing_at = $time WHERE id = $uid");
}

pg_close($db);
?>
