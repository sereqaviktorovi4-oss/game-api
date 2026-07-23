<?php 
include 'db.php'; 
session_start();
include 'header.php'; 

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$my_id = (int)$_SESSION['user_id'];

// Логика удаления друга (без изменений)
if (isset($_GET['delete'])) {
    $f_id = (int)$_GET['delete'];
    $db->query("DELETE FROM friends WHERE (user_id=$my_id AND friend_id=$f_id) OR (user_id=$f_id AND friend_id=$my_id)");
    header("Location: friends.php");
    exit;
}

// ОБНОВЛЕННЫЙ ЗАПРОС: добавили u.last_seen и u.platform
$friends_query = $db->query("
    SELECT u.id, u.username, u.gender, u.avatar_path, u.last_seen, u.platform,
    (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = $my_id AND is_read = 0) as unread_msgs
    FROM users u 
    JOIN friends f ON (u.id = f.friend_id OR u.id = f.user_id) 
    WHERE (f.user_id = $my_id OR f.friend_id = $my_id) 
    AND f.status = 'accepted' 
    AND u.id != $my_id
    ORDER BY unread_msgs DESC, u.username ASC
");
?>

<h2 style="color: var(--accent); margin-bottom: 20px;"><i class="fa-solid fa-user-group"></i> Мои друзья</h2>

<div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
    <?php if ($friends_query->num_rows > 0): ?>
        <?php while($f = $friends_query->fetch_assoc()): ?>
            <?php 
                // ЛОГИКА ОПРЕДЕЛЕНИЯ ОНЛАЙНА
                $is_online = false;
                if (!empty($f['last_seen'])) {
                    $last_active = strtotime($f['last_seen']);
                    $current_time = time();
                    // Если активность была менее 2 минут назад (120 секунд)
                    if (($current_time - $last_active) < 120) {
                        $is_online = true;
                    }
                }
            ?>
            <div style="display: flex; justify-content: space-between; align-items: center; background: #252530; padding: 15px; border-radius: 15px; border: 1px solid <?php echo $is_online ? 'var(--accent)' : '#333'; ?>;">
                
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="position: relative; width: 50px; height: 50px;">
                        <?php if($f['avatar_path']): ?>
                            <img src="<?php echo $f['avatar_path']; ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 2px solid <?php echo $is_online ? '#00ff00' : 'var(--primary)'; ?>;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: #1a1a24; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; border: 2px solid <?php echo $is_online ? '#00ff00' : '#444'; ?>;">
                                <?php echo ($f['gender'] == 'm' ? '👦' : '👧'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: <?php echo $is_online ? '#00ff00' : '#555'; ?>; border-radius: 50%; border: 2px solid #252530;"></div>

                        <?php if($f['unread_msgs'] > 0): ?>
                            <div style="position: absolute; top: -5px; right: -5px; background: #ff4444; color: white; font-size: 0.7rem; padding: 2px 7px; border-radius: 10px; border: 2px solid #252530; animation: pulse 1.5s infinite;">
                                +<?php echo $f['unread_msgs']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <a href="profile.php?id=<?php echo $f['id']; ?>" style="text-decoration: none; color: #fff; font-weight: bold;"><?php echo $f['username']; ?></a>
                        <div style="font-size: 0.75rem; color: <?php echo $is_online ? 'var(--accent)' : '#777'; ?>;">
                            <?php 
                                if ($is_online) {
                                    echo ($f['platform'] == 'game') ? '🎮 В игре Love City' : '🌐 На сайте';
                                } else {
                                    echo 'Офлайн';
                                }
                            ?>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php if ($is_online && $f['platform'] == 'game'): ?>
                        <a href="lovecity://visit=<?php echo $f['id']; ?>" class="btn-action" style="padding: 8px 15px; font-size: 0.75rem; text-decoration: none; background: #ff00ff; color: white; border: none;" title="Телепортироваться к другу в игре">
                            <i class="fa-solid fa-plane"></i> В гости
                        </a>
                    <?php endif; ?>

                    <a href="messages.php?to=<?php echo $f['id']; ?>" class="btn-action" style="padding: 8px 15px; font-size: 0.75rem; text-decoration: none; background: <?php echo ($f['unread_msgs'] > 0) ? 'var(--accent)' : 'var(--primary)'; ?>; <?php echo ($f['unread_msgs'] > 0) ? 'color: #000;' : ''; ?>">
                        <i class="fa-solid fa-envelope"></i> Читать
                    </a>
                    <a href="friends.php?delete=<?php echo $f['id']; ?>" style="color: #555; font-size: 1.1rem; padding: 5px;" onclick="return confirm('Удалить из друзей?')" title="Удалить">
                        <i class="fa-solid fa-user-xmark"></i>
                    </a>
                </div>

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; background: #1a1a24; border-radius: 15px; color: #555;">
            <i class="fa-solid fa-ghost" style="font-size: 3rem; margin-bottom: 10px;"></i>
            <p>У вас пока нет друзей. Найдите их в <a href="friends_online.php" style="color: var(--accent);">списке жителей</a>!</p>
        </div>
    <?php endif; ?>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); box-shadow: 0 0 10px rgba(255,68,68,0.5); }
    100% { transform: scale(1); }
}
</style>

<div style="margin-top: 30px; text-align: center;">
    <a href="friends_online.php" class="btn-action" style="text-decoration: none; background: #333;">Найти новых друзей</a>
</div>

<?php echo "</div></body></html>"; ?>

