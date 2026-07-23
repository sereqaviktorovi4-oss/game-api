<?php 
include 'db.php'; 
session_start();
include 'header.php'; 

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$my_id = (int)$_SESSION['user_id'];
$view_id = isset($_GET['id']) ? (int)$_GET['id'] : $my_id;
$is_own_profile = ($view_id === $my_id);

$res = $db->query("SELECT * FROM users WHERE id=$view_id");
$user = $res->fetch_assoc();

if (!$user) { echo "<h2 style='text-align:center;'>Житель не найден в базе данных города.</h2>"; exit; }

// Проверка статуса дружбы
$friend_status = 'none';
$is_sender = false; 

if (!$is_own_profile) {
    $check_f = $db->query("SELECT * FROM friends WHERE (user_id=$my_id AND friend_id=$view_id) OR (user_id=$view_id AND friend_id=$my_id) LIMIT 1");
    if ($f_data = $check_f->fetch_assoc()) {
        $friend_status = $f_data['status'];
        if ($f_data['user_id'] == $my_id) $is_sender = true;
    }
}

$photos = $db->query("SELECT * FROM user_photos WHERE user_id=$view_id ORDER BY id DESC LIMIT 12");
$friends_count = $db->query("SELECT id FROM friends WHERE (user_id=$view_id OR friend_id=$view_id) AND status='accepted'")->num_rows;
?>

<style>
    .profile-grid { display: grid; grid-template-columns: 300px 1fr; gap: 25px; }
    @media (max-width: 800px) { .profile-grid { grid-template-columns: 1fr; } }
    
    @keyframes rotate-border { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .avatar-container { position: relative; width: 160px; height: 160px; margin: 0 auto 20px; }
    .avatar-border { position: absolute; inset: -8px; border: 2px dashed var(--accent); border-radius: 50%; animation: rotate-border 12s linear infinite; opacity: 0.4; }
    .avatar-main { width: 100%; height: 100%; background: #1c1c24; border-radius: 50%; overflow: hidden; border: 4px solid var(--primary); position: relative; z-index: 2; box-shadow: 0 0 20px rgba(142,45,226,0.4); }
    .online-indicator { position: absolute; bottom: 12px; right: 12px; width: 22px; height: 22px; background: #00ff00; border-radius: 50%; border: 3px solid #252530; z-index: 5; box-shadow: 0 0 10px #00ff00; }
    
    .stat-box { background: #13131a; padding: 10px; border-radius: 8px; text-align: center; border-bottom: 2px solid var(--primary); text-decoration: none; transition: 0.3s; color: inherit; }
    .stat-box:hover { border-color: var(--accent); background: #1a1a24; }

    .gift-shelf { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; background: rgba(0,0,0,0.3); padding: 12px; border-radius: 15px; border: 1px solid #333; margin-top: 15px; }
    .gift-slot { width: 55px; height: 55px; background: radial-gradient(circle, #2a2a35, #13131a); border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid #444; overflow: hidden; position: relative; }
    .gift-premium-img { width: 45px; height: 45px; object-fit: contain; filter: drop-shadow(0 5px 8px rgba(0,0,0,0.5)); animation: gift-float 3s ease-in-out infinite; }
    
    @keyframes gift-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

    .gift-modal { display: none; position: fixed; z-index: 9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.9); backdrop-filter: blur(8px); align-items: center; justify-content: center; }
    .shop-card { background: #1c1c24; width: 95%; max-width: 550px; border-radius: 25px; border: 1px solid #444; padding: 20px; text-align: center; }
    .shop-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 15px 0; }
    .shop-item { background: #252530; padding: 10px; border-radius: 15px; cursor: pointer; border: 1px solid #333; transition: 0.3s; }
    .shop-item:hover { border-color: gold; transform: translateY(-3px); }
    .shop-item img { width: 50px; height: 50px; object-fit: contain; margin-bottom: 5px; }
    .shop-item span { display: block; font-size: 0.6rem; color: #888; text-transform: uppercase; }

    /* Анимация и стиль кнопки «Перейти в свою локацию» */
    .btn-personal-location {
        background: linear-gradient(45deg, #ff00ff, #8e2de2);
        color: #fff;
        text-decoration: none;
        font-size: 0.85rem;
        padding: 12px;
        border-radius: 8px;
        font-weight: bold;
        box-shadow: 0 0 15px rgba(255, 0, 255, 0.4);
        transition: 0.3s ease;
        border: none;
        display: block;
        margin-top: 5px;
    }
    .btn-personal-location:hover {
        transform: translateY(-2px);
        box-shadow: 0 0 25px rgba(255, 0, 255, 0.7);
        color: #fff;
    }
</style>

<div class="profile-grid">
    <div style="background: #252530; padding: 25px; border-radius: 20px; border: 1px solid #333; text-align: center;">
        <div class="avatar-container">
            <div class="avatar-border"></div>
            <div class="avatar-main">
                <?php if (!empty($user['avatar_path'])): ?>
                    <img src="<?php echo $user['avatar_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="font-size: 5rem; margin-top: 25px;"><?php echo ($user['gender'] == 'm' ? '👦' : '👧'); ?></div>
                <?php endif; ?>
            </div>
            <?php if ($user['last_active'] > date('Y-m-d H:i:s', strtotime('-5 minutes'))): ?>
                <div class="online-indicator" title="В игре"></div>
            <?php endif; ?>
        </div>

        <h2 style="color: var(--accent); margin-bottom: 5px;"><?php echo $user['username']; ?></h2>
        
        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
            <?php if ($is_own_profile): ?>
                <a href="edit_profile.php" class="btn-action" style="text-decoration: none; font-size: 0.85rem; padding: 10px;"><i class="fa-solid fa-user-gear"></i> Настройки профиля</a>
                
                <!-- КНОПКА ПЕРЕХОДА В ЛИЧНУЮ ЛОКАЦИЮ -->
                <?php if (!empty($user['plot_coords'])): ?>
                    <a href="gamedirect://teleport?location=<?php echo urlencode($user['plot_coords']); ?>" class="btn-personal-location">
                        <i class="fa-solid fa-house-chimney"></i> 🏠 В СВОЮ ЛОКАЦИЮ (<?php echo htmlspecialchars($user['plot_coords']); ?>)
                    </a>
                <?php endif; ?>

            <?php else: ?>
                <a href="messages.php?to=<?php echo $view_id; ?>" class="btn-action" style="text-decoration: none; font-size: 0.85rem; padding: 10px;"><i class="fa-solid fa-paper-plane"></i> Написать сообщение</a>
                <button onclick="document.getElementById('giftModal').style.display='flex'" class="btn-action" style="background: #1a1a24; color: gold; border: 1px solid #444; font-size: 0.85rem; padding: 10px; cursor: pointer;"><i class="fa-solid fa-gift"></i> Сделать подарок</button>
                
                <?php if ($friend_status == 'none'): ?>
                    <a href="friends_logic.php?action=add&id=<?php echo $view_id; ?>" class="btn-action" style="background: var(--secondary); text-decoration: none; font-size: 0.85rem; padding: 10px;"><i class="fa-solid fa-user-plus"></i> Добавить в друзья</a>
                <?php elseif ($friend_status == 'pending'): ?>
                    <?php if ($is_sender): ?>
                        <button class="btn-action" style="background: #444; font-size: 0.85rem; padding: 10px; cursor: default;" disabled><i class="fa-solid fa-clock"></i> Заявка отправлена</button>
                    <?php else: ?>
                        <a href="friends_logic.php?action=accept&id=<?php echo $view_id; ?>" class="btn-action" style="background: #28a745; text-decoration: none; font-size: 0.85rem; padding: 10px;"><i class="fa-solid fa-check"></i> Принять дружбу</a>
                    <?php endif; ?>
                <?php elseif ($friend_status == 'accepted'): ?>
                    <a href="friends_logic.php?action=delete&id=<?php echo $view_id; ?>" class="btn-action" style="background: #6610f2; text-decoration: none; font-size: 0.85rem; padding: 10px;" onclick="return confirm('Удалить из друзей?')"><i class="fa-solid fa-user-minus"></i> Удалить из друзей</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
            <div class="stat-box">
                <div style="font-size: 1.2rem; color: var(--accent);"><?php echo $friends_count; ?></div>
                <div style="font-size: 0.7rem; color: #777; text-transform: uppercase;">Друзей</div>
            </div>
            <div class="stat-box">
                <div style="font-size: 1.2rem; color: #ff00ff;">Lv. 1</div>
                <div style="font-size: 0.7rem; color: #777; text-transform: uppercase;">Уровень</div>
            </div>
        </div>

        <p style="font-size: 0.65rem; color: #555; text-transform: uppercase; margin-bottom: 5px;">Трофеи жителя</p>
        <div class="gift-shelf">
            <?php
            $gifts = $db->query("SELECT * FROM user_gifts WHERE receiver_id = $view_id ORDER BY id DESC LIMIT 12");
            if($gifts->num_rows > 0):
                while($g = $gifts->fetch_assoc()): ?>
                    <div class="gift-slot"><img src="<?php echo htmlspecialchars($g['gift_icon']); ?>" class="gift-premium-img"></div>
                <?php endwhile; 
            else: echo "<span style='color:#444;font-size:0.7rem;'>Пусто</span>"; endif; ?>
        </div>

        <div style="text-align: left; font-size: 0.85rem; color: #bbb; background: #1a1a24; padding: 15px; border-radius: 12px; margin-top: 20px;">
            <p style="margin: 5px 0;"><i class="fa-solid fa-location-arrow" style="width: 20px; color: var(--accent);"></i> Район: <b><?php echo $user['district_name'] ?: 'Love Sector A'; ?></b></p>
            <p style="margin: 5px 0;"><i class="fa-solid fa-house" style="width: 20px; color: var(--accent);"></i> Участок: <b><?php echo $user['plot_coords'] ?: 'Не занят'; ?></b></p>
            <p style="margin: 5px 0;"><i class="fa-solid fa-coins" style="width: 20px; color: gold;"></i> Капитал: <span style="color:gold;"><?php echo number_format($user['citymoney']); ?></span></p>
        </div>
    </div>

    <div>
        <div style="background: #252530; padding: 20px; border-radius: 15px; border-left: 6px solid #ff00ff; margin-bottom: 25px;">
            <h4 style="margin: 0 0 10px 0; color: #aaa; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Клубный девиз</h4>
            <div style="font-size: 1.2rem; font-style: italic; color: #efefef;">"<?php echo htmlspecialchars($user['status_text'] ?: 'Я здесь новый житель!'); ?>"</div>
        </div>

        <div style="background: #252530; padding: 25px; border-radius: 20px; border: 1px solid #333;">
            <h3 style="margin: 0 0 20px 0; font-size: 1.1rem;"><i class="fa-solid fa-images" style="color: var(--accent);"></i> Галерея жителя</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px;">
                <?php if ($photos->num_rows > 0): ?>
                    <?php while($ph = $photos->fetch_assoc()): ?>
                        <div style="aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; border: 2px solid #1c1c24;">
                            <img src="<?php echo $ph['photo_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #555; background: #1c1c24; border-radius: 10px; border: 1px dashed #333;">
                        <i class="fa-solid fa-camera" style="font-size: 1.5rem; margin-bottom: 10px; opacity: 0.3;"></i>
                        <p>У этого жителя пока нет фотографий.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="giftModal" class="gift-modal">
    <div class="shop-card">
        <h3 style="color: gold; margin: 0;">VIP БУТИК</h3>
        <p style="color: #666; font-size: 0.7rem;">Стоимость любого предмета: 30 СМ</p>
        
        <div class="shop-grid">
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/3462/3462153.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/3462/3462153.png"><span>Розы</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/1047/1047327.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/1047/1047327.png"><span>Букет</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/3144/3144760.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/3144/3144760.png"><span>Торт</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/4213/4213940.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/4213/4213940.png"><span>Мишка</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/741/741407.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/741/741407.png"><span>Ламба</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/2555/2555013.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/2555/2555013.png"><span>Гелик</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/900/900230.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/900/900230.png"><span>Яхта</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/3125/3125713.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/3125/3125713.png"><span>Джет</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/2850/2850389.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/2850/2850389.png"><span>Алмаз</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/4149/4149883.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/4149/4149883.png"><span>Rolex</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/1042/1042312.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/1042/1042312.png"><span>Корона</span>
            </div>
            <div class="shop-item" onclick="sendGift('https://cdn-icons-png.flaticon.com/512/3105/3105801.png')">
                <img src="https://cdn-icons-png.flaticon.com/512/3105/3105801.png"><span>Шампань</span>
            </div>
        </div>
        
        <button onclick="document.getElementById('giftModal').style.display='none'" style="background: #333; color: #fff; border: none; padding: 12px; border-radius: 12px; width: 100%; cursor: pointer;">Отмена</button>
    </div>
</div>

<script>
function sendGift(url) {
    if(!confirm("Отправить этот подарок за 30 CM?")) return;
    
    // Используем FormData для надежной отправки
    let formData = new URLSearchParams();
    formData.append('to_id', '<?php echo $view_id; ?>');
    formData.append('icon', url);

    fetch('send_gift.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData.toString()
    })
    .then(r => r.text())
    .then(res => {
        let cleanRes = res.trim();
        if (cleanRes === "SUCCESS") {
            location.reload();
        } else if (cleanRes === "LOW_MONEY") {
            alert("Недостаточно CM!");
        } else {
            alert("Ошибка сервера: " + cleanRes);
        }
    })
    .catch(err => alert("Ошибка сети: " + err));
}
</script>

<?php echo "</div></body></html>"; ?>
