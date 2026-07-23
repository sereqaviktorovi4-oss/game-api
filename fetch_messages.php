<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$my_username = $_SESSION['username'] ?? '';

// Подтягиваем сообщения и ID пользователя для меню
$sql = "
    SELECT c.*, u.id as u_id 
    FROM chat c 
    LEFT JOIN users u ON c.username = u.username 
    ORDER BY c.id DESC LIMIT 40
";

$result = pg_query($db, $sql);
$rows = [];

if ($result) {
    while($m = pg_fetch_assoc($result)) {
        $is_me = ($m['username'] === $my_username);
        
        // Прямое управление позицией (flex-end - право, flex-start - лево)
        $side = $is_me ? 'flex-end' : 'flex-start';
        $bg = $is_me ? '#2d1b4d' : '#1c1c24'; // Фиолетовый для тебя, темный для других
        $border = $is_me ? 'border-right: 3px solid var(--accent); border-bottom-right-radius: 2px;' : 'border-left: 3px solid var(--primary); border-bottom-left-radius: 2px;';
        $nick_color = $is_me ? 'var(--accent)' : 'var(--primary)';
        
        $time = date('H:i', strtotime($m['created_at']));

        // Используем структуру в точности как в привате
        $html = "
        <div class='msg-row' style='display: flex; flex-direction: column; width: 100%; align-items: {$side}; margin-bottom: 10px;'>
            <div class='chat-nick' data-id='{$m['u_id']}' style='color: {$nick_color}; cursor: pointer; font-weight: bold; font-size: 0.75rem; margin-bottom: 2px; padding: 0 10px;'>
                " . htmlspecialchars($m['username']) . "
            </div>
            <div class='bubble' style='max-width: 85%; padding: 10px 14px; border-radius: 18px; font-size: 0.95rem; background: {$bg}; color: #fff; {$border} box-shadow: 0 2px 5px rgba(0,0,0,0.3);'>
                " . htmlspecialchars($m['message']) . "
                <span style='font-size: 0.6rem; opacity: 0.4; display: block; margin-top: 4px; text-align: right;'>{$time}</span>
            </div>
        </div>";
        
        $rows[] = $html;
    }
}

// Новые сообщения внизу
echo implode('', array_reverse($rows));
?>
