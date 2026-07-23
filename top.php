<?php 
include 'db.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'header.php'; 

// Проверка: залогинен ли пользователь (чтобы работала кнопка "Добавить")
$my_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// --- ЛОГИКА ДОБАВЛЕНИЯ В ДРУЗЬЯ ---
if (isset($_GET['add_friend']) && $my_id) {
    $friend_id = intval($_GET['add_friend']);
    
    // Проверяем, не добавляем ли мы сами себя и нет ли уже такой связи
    $check = pg_query($db, "SELECT id FROM friends WHERE (user_id=$my_id AND friend_id=$friend_id) OR (user_id=$friend_id AND friend_id=$my_id)");
    
    if ($check && pg_num_rows($check) == 0 && $my_id != $friend_id) {
        // Добавляем запись (для простоты сразу подтвержденную)
        pg_query($db, "INSERT INTO friends (user_id, friend_id, status) VALUES ($my_id, $friend_id, 'accepted')");
        echo "<script>alert('Пользователь добавлен в друзья!'); window.location.href='top.php';</script>";
    }
}

// --- ЛОГИКА ПОИСКА ---
$search_query = "";
if (isset($_POST['search'])) {
    $search_term = pg_escape_string($db, trim($_POST['search']));
    $search_query = "WHERE username LIKE '%$search_term%'";
}

// Получаем список жителей
$users = pg_query($db, "SELECT id, username, gender, birth_year, plot_coords FROM users $search_query ORDER BY id DESC LIMIT 50");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="color: var(--accent); margin: 0;"><i class="fa-solid fa-users"></i> Жители города</h2>
    
    <form method="POST" style="display: flex; gap: 5px;">
        <input type="text" name="search" placeholder="Найти по нику..." style="margin: 0; padding: 8px;">
        <button type="submit" class="btn-action" style="padding: 8px 15px;"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
</div>

<div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; background: #1a1a24; border-radius: 10px; overflow: hidden;">
        <thead>
            <tr style="background: var(--primary); color: white; text-align: left;">
                <th style="padding: 15px;">Житель</th>
                <th style="padding: 15px;">Пол</th>
                <th style="padding: 15px;">Год рождения</th>
                <th style="padding: 15px;">Участок</th>
                <th style="padding: 15px; text-align: center;">Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($users && pg_num_rows($users) > 0): ?>
                <?php while($row = pg_fetch_assoc($users)): ?>
                <tr style="border-bottom: 1px solid #333; transition: 0.3s;" onmouseover="this.style.background='#252530'" onmouseout="this.style.background='none'">
                    <td style="padding: 12px;">
                        <i class="fa-solid fa-circle" style="color: #00ff00; font-size: 0.6rem; margin-right: 10px;"></i>
                        <b><?php echo htmlspecialchars($row['username']); ?></b>
                    </td>
                    <td style="padding: 12px; color: <?php echo ($row['gender'] == 'm' ? '#00f2ff' : '#ff00ff'); ?>;">
                        <i class="fa-solid <?php echo ($row['gender'] == 'm' ? 'fa-mars' : 'fa-venus'); ?>"></i>
                        <?php echo ($row['gender'] == 'm' ? 'Мужчина' : 'Женщина'); ?>
                    </td>
                    <td style="padding: 12px;"><?php echo (int)$row['birth_year']; ?></td>
                    <td style="padding: 12px; color: #aaa;">
                        <i class="fa-solid fa-location-dot"></i> 
                        <?php echo !empty($row['plot_coords']) ? htmlspecialchars($row['plot_coords']) : 'Нет дома'; ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <?php if ($my_id && $my_id != (int)$row['id']): ?>
                            <a href="top.php?add_friend=<?php echo (int)$row['id']; ?>" class="btn-action" style="padding: 5px 10px; text-decoration: none; font-size: 0.8rem;" title="Добавить в друзья">
                                <i class="fa-solid fa-user-plus"></i>
                            </a>
                        <?php elseif (!$my_id): ?>
                            <span style="font-size: 0.7rem; color: #555;">Войдите, чтобы добавить</span>
                        <?php else: ?>
                            <span style="font-size: 0.7rem; color: var(--accent);">Это вы</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 30px; text-align: center; color: #777;">Жители не найдены...</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 20px; text-align: center;">
    <a href="friends.php" style="color: var(--accent); text-decoration: none;">
        <i class="fa-solid fa-arrow-right"></i> Перейти в список моих друзей
    </a>
</div>

<?php 
pg_close($db);
echo "</div></body></html>"; 
?>
