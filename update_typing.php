<?php
include 'db.php';
session_start();
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $status = (int)$_GET['status'];
    // Обновляем время печати
    $time = ($status == 1) ? time() : 0;
    $db->query("UPDATE users SET is_typing_at = $time WHERE id = $uid");
}
?>
