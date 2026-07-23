<?php
// Вывод ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}

$my_id = (int)$_SESSION['user_id'];
$to_id = isset($_GET['to']) ? (int)$_GET['to'] : 0;

if ($to_id <= 0) {
    exit("Invalid user ID");
}

// Помечаем сообщения как прочитанные
pg_query($db, "UPDATE messages SET is_read = 1 WHERE sender_id = $to_id AND receiver_id = $my_id");

// Выбираем переписку
$sql = "SELECT * FROM messages 
        WHERE (sender_id = $my_id AND receiver_id = $to_id) 
           OR (sender_id = $to_id AND receiver_id = $my_id) 
        ORDER BY created_at ASC";

$res = pg_query($db, $sql);

if ($res) {
    while ($m = pg_fetch_assoc($res)) {
        $class = ($m['sender_id'] == $my_id) ? 'sent' : 'rcvd';
        
        // Проверяем, является ли сообщение VIP (золотым)
        $vip_class = (isset($m['is_vip']) && $m['is_vip'] == 1) ? 'vip-msg' : '';
        
        $status = ($class == 'sent') ? ($m['is_read'] == 1 ? ' <i class="fa-solid fa-check-double"></i>' : ' <i class="fa-solid fa-check"></i>') : '';
        
        // Добавляем $vip_class в основной контейнер сообщения
        echo "<div class='msg-row $class $vip_class'>
                <div class='bubble'>
                    " . htmlspecialchars($m['message']) . "
                    <span class='time'>" . date('H:i', strtotime($m['created_at'])) . $status . "</span>
                </div>
              </div>";
    }
}

pg_close($db);
?>
