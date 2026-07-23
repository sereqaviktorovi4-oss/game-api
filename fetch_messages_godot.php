<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

// Получаем последние 20 сообщений из общего чата
// Если у тебя PDO, код будет выглядеть так:
try {
    $stmt = $pdo->prepare("SELECT username, message, user_id FROM chat WHERE room_id = 'global' ORDER BY id DESC LIMIT 20");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Переворачиваем массив, чтобы старые сообщения были сверху, а новые снизу
    $messages = array_reverse($messages);
    
    echo json_encode(["status" => "success", "messages" => $messages], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
