<?php
include 'db.php';

// Переключаем заголовок в режим JSON, так как Godot общается через JSON
header('Content-Type: application/json; charset=utf-8');

// Получаем сырые JSON данные из тела POST-запроса от Godot
$inputData = file_get_contents('php://input');
$request = json_decode($inputData, true);

if (!$request) {
    echo json_encode(["error" => "INVALID_JSON"]);
    exit;
}

// Извлекаем базовые параметры, которые Godot передает всегда
$user_id = isset($request['user_id']) ? (int)$request['user_id'] : 0;
$action = isset($request['action']) ? $request['action'] : '';

if ($user_id <= 0) {
    echo json_encode(["error" => "AUTH_REQUIRED"]);
    exit;
}

// =========================================================================
// 1. ПОЛУЧЕНИЕ СПИСКА АКТИВНЫХ ДИАЛОГОВ (Левая колонка мессенджера)
// =========================================================================
if ($action === 'get_chats') {
    // Выбираем всех пользователей, с которыми у нас есть входящие или исходящие сообщения
    $query = "SELECT DISTINCT 
                CASE WHEN sender_id = $user_id THEN receiver_id ELSE sender_id END AS chat_user_id 
              FROM messages 
              WHERE sender_id = $user_id OR receiver_id = $user_id";
    
    $result = $db->query($query);
    $chats = [];
    
    while ($row = $result->fetch_assoc()) {
        $chat_user_id = (int)$row['chat_user_id'];
        
        // Получаем имя собеседника
        $user_res = $db->query("SELECT username FROM users WHERE id = $chat_user_id");
        if ($user_user = $user_res->fetch_assoc()) {
            $chats[] = [
                "id" => $chat_user_id,
                "username" => $user_user['username']
            ];
        }
    }
    
    echo json_encode(["chats" => $chats]);
    exit;
}

// =========================================================================
// 2. ПОЛУЧЕНИЕ ИСТОРИИ ПЕРЕПИСКИ (Правая колонка мессенджера)
// =========================================================================
if ($action === 'get_history') {
    $target_id = isset($request['target_id']) ? (int)$request['target_id'] : 0;
    
    if ($target_id <= 0) {
        echo json_encode(["messages" => []]);
        exit;
    }
    
    // При открытии истории отмечаем все входящие сообщения от этого пользователя как прочитанные
    $db->query("UPDATE messages SET is_read = 1 WHERE sender_id = $target_id AND receiver_id = $user_id");
    
    // Достаем историю сообщений между двумя игроками
    $query = "SELECT m.*, u.username AS sender_name 
              FROM messages m
              JOIN users u ON m.sender_id = u.id
              WHERE (m.sender_id = $user_id AND m.receiver_id = $target_id)
                 OR (m.sender_id = $target_id AND m.receiver_id = $user_id)
              ORDER BY m.id ASC";
              
    $result = $db->query($query);
    $messages = [];
    
    while ($row = $result->fetch_assoc()) {
        $text = $row['message'];
        
        // Если это VIP (золотое) сообщение, добавляем золотой тег для RichTextLabel в Godot
        if ((int)$row['is_vip'] === 1) {
            $text = "[color=gold][b]👑 " . $text . "[/b][/color]";
        }
        
        $messages[] = [
            "id" => (int)$row['id'],
            "sender_id" => (int)$row['sender_id'],
            "sender" => $row['sender_name'],
            "text" => $text,
            "time" => date("H:i", strtotime($row['created_at']))
        ];
    }
    
    echo json_encode(["messages" => $messages]);
    exit;
}

// =========================================================================
// 3. ОТПРАВКА НОВОГО СООБЩЕНИЯ ИЗ GODOT В БД
// =========================================================================
if ($action === 'send_message') {
    $target_id = isset($request['target_id']) ? (int)$request['target_id'] : 0;
    $message = isset($request['message']) ? $db->real_escape_string(trim($request['message'])) : '';
    $is_vip = isset($request['is_vip']) ? (int)$request['is_vip'] : 0; // На будущее, если добавишь чекбокс в Godot
    
    if ($target_id <= 0 || $message === '') {
        echo json_encode(["error" => "EMPTY_DATA"]);
        exit;
    }
    
    // Проверка на VIP (золотое сообщение): списываем 10 СМ, если это VIP
    if ($is_vip === 1) {
        $user_data = $db->query("SELECT citymoney FROM users WHERE id = $user_id")->fetch_assoc();
        if ((int)$user_data['citymoney'] < 10) {
            echo json_encode(["error" => "ERROR_MONEY"]);
            exit;
        }
        // Снимаем деньги со счета
        $db->query("UPDATE users SET citymoney = citymoney - 10 WHERE id = $user_id");
    }
    
    // Сохраняем в таблицу сообщений
    $insert = "INSERT INTO messages (sender_id, receiver_id, message, is_read, is_vip, created_at) 
               VALUES ($user_id, $target_id, '$message', 0, $is_vip, NOW())";
               
    if ($db->query($insert)) {
        echo json_encode(["status" => "OK", "message_id" => $db->insert_id]);
    } else {
        echo json_encode(["error" => "DB_ERROR"]);
    }
    exit;
}

// =========================================================================
// 4. ДЕФОЛТНОЕ ПОВЕДЕНИЕ (Старый опрос количества новых непрочитанных ЛС)
// =========================================================================
// Вызывается, когда Godot проверяет наличие обновлений: { "user_id": X, "only_unread": true }
$query = "SELECT m.*, u.username AS sender_name 
          FROM messages m 
          JOIN users u ON m.sender_id = u.id 
          WHERE m.receiver_id = $user_id AND m.is_read = 0 
          ORDER BY m.id ASC";

$result = $db->query($query);
$unread_messages = [];

while ($row = $result->fetch_assoc()) {
    $unread_messages[] = [
        "id" => (int)$row['id'],
        "sender_id" => (int)$row['sender_id'],
        "sender_name" => $row['sender_name'],
        "message" => $row['message']
    ];
}

echo json_encode(["messages" => $unread_messages]);
?>
