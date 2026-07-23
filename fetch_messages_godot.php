<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

try {
    // Делаем запрос через PostgreSQL
    $result = pg_query($db, "SELECT username, message, user_id FROM chat WHERE room_id = 'global' ORDER BY id DESC LIMIT 20");
    
    if (!$result) {
        throw new Exception(pg_last_error($db));
    }
    
    $messages = pg_fetch_all($result);
    
    // Если сообщений нет, pg_fetch_all вернет false, поэтому приводим к пустому массиву
    if (!$messages) {
        $messages = [];
    } else {
        // Переворачиваем массив, чтобы старые сообщения были сверху, а новые снизу
        $messages = array_reverse($messages);
    }
    
    echo json_encode(["status" => "success", "messages" => $messages], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
