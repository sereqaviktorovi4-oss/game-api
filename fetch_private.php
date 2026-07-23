<?php
include 'db.php';
session_start();
$my_id = (int)$_SESSION['user_id'];
$to_id = (int)$_GET['to'];

// Помечаем сообщения как прочитанные
$db->query("UPDATE messages SET is_read = 1 WHERE sender_id = $to_id AND receiver_id = $my_id");

// Выбираем переписку (добавь проверку, что колонка is_vip существует в базе)
$sql = "SELECT * FROM messages 
        WHERE (sender_id = $my_id AND receiver_id = $to_id) 
        OR (sender_id = $to_id AND receiver_id = $my_id) 
        ORDER BY created_at ASC";

$res = $db->query($sql);
while($m = $res->fetch_assoc()) {
    $class = ($m['sender_id'] == $my_id) ? 'sent' : 'rcvd';
    
    // Проверяем, является ли сообщение VIP (золотым)
    $vip_class = (isset($m['is_vip']) && $m['is_vip'] == 1) ? 'vip-msg' : '';
    
    $status = ($class == 'sent') ? ($m['is_read'] ? ' <i class="fa-solid fa-check-double"></i>' : ' <i class="fa-solid fa-check"></i>') : '';
    
    // Добавляем $vip_class в основной контейнер сообщения
    echo "<div class='msg-row $class $vip_class'>
            <div class='bubble'>
                " . htmlspecialchars($m['message']) . "
                <span class='time'>" . date('H:i', strtotime($m['created_at'])) . $status . "</span>
            </div>
          </div>";
}
?>
