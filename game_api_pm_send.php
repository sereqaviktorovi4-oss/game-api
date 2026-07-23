<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

$raw_json = file_get_contents('php://input');
$data = json_decode($raw_json, true);

if (!$data || !isset($data['sender_id']) || !isset($data['target_name']) || !isset($data['message'])) {
    echo json_encode(["status" => "error", "message" => "Недостаточно JSON-параметров"]);
    exit;
}

$sender_id = (int)$data['sender_id'];
$target_name = pg_escape_string($db, trim($data['target_name']));
$message = pg_escape_string($db, trim($data['message']));

if (empty($message)) {
    pg_close($db);
    exit(json_encode(["status" => "error", "message" => "Текст сообщения пуст"]));
}

// Ищем получателя по никнейму
$res_r = pg_query($db, "SELECT id FROM users WHERE username = '$target_name'");
$receiver_data = ($res_r) ? pg_fetch_assoc($res_r) : null;

if (!$receiver_data) {
    pg_close($db);
    exit(json_encode(["status" => "error", "message" => "Получатель не найден"]));
}

$receiver_id = (int)$receiver_data['id'];

$sql = "INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at, is_vip) 
        VALUES ($sender_id, $receiver_id, '$message', 0, NOW(), 0)";
        
$res = pg_query($db, $sql);
if ($res) {
    pg_close($db);
    echo json_encode(["status" => "success", "message" => "Успешно отправлено"]);
} else {
    $db_error = pg_last_error($db);
    pg_close($db);
    echo json_encode(["status" => "error", "message" => "Ошибка БД: " . $db_error]);
}
?>
