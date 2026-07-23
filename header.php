<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$notif_friends = 0; $notif_msgs = 0; $my_money = 0;

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $db->query("UPDATE users SET last_seen = NOW(), platform = 'web' WHERE id = $uid");
    $user_data = $db->query("SELECT citymoney FROM users WHERE id=$uid")->fetch_assoc();
    $my_money = $user_data['citymoney'] ?? 0;
    $notif_friends = $db->query("SELECT COUNT(*) as cnt FROM friends WHERE friend_id = $uid AND status = 'pending'")->fetch_assoc()['cnt'] ?? 0;
    $notif_msgs = $db->query("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id = $uid AND is_read = 0")->fetch_assoc()['cnt'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Love City Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #8e2de2; --secondary: #4a00e0; --accent: #00f2ff; --bg: #0b0b0e; --panel: #1c1c24; --gold: #ffd700; }
        
        /* МАГИЯ СИЯНИЯ ПОДАРКОВ */
        .gift-premium { display: inline-block; position: relative; transition: 0.4s; filter: drop-shadow(0 0 8px rgba(0,0,0,0.8)); }
        
        @keyframes shine-anim {
            0% { filter: brightness(1) contrast(1.2); transform: scale(1); }
            50% { filter: brightness(1.5) contrast(1.5) drop-shadow(0 0 15px currentColor); transform: scale(1.05); }
            100% { filter: brightness(1) contrast(1.2); transform: scale(1); }
        }

        .gift-gold { 
            background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 45%, #b38728 70%, #fbf5b7 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; 
            animation: shine-anim 4s infinite ease-in-out; color: #ffcf4d;
        }
        .gift-diamond { 
            background: linear-gradient(135deg, #e0f7fa 0%, #00e5ff 50%, #ffffff 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; 
            animation: shine-anim 5s infinite ease-in-out; color: #00f2ff;
        }
        .gift-ruby { 
            background: linear-gradient(135deg, #ff0055 0%, #ff73a1 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; 
            animation: shine-anim 3s infinite ease-in-out; color: #ff0055;
        }
        .gift-royal { 
            background: linear-gradient(135deg, #8e2de2 0%, #ff00f2 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; 
            animation: shine-anim 6s infinite ease-in-out; color: #8e2de2;
        }

        body { background: var(--bg); color: #efefef; font-family: 'Segoe UI', sans-serif; margin: 0; padding-bottom: 50px; }
        header { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 20px 0; text-align: center; }
        header h1 { margin: 0; font-size: 2.2rem; letter-spacing: 4px; color: #fff; }
        nav { background: #16161d; display: flex; justify-content: center; flex-wrap: wrap; padding: 10px; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 1000; }
        nav a { color: #fff; text-decoration: none; padding: 10px 15px; font-weight: bold; font-size: 0.8rem; border-radius: 5px; transition: 0.3s; position: relative; }
        nav a:hover { color: var(--accent); background: rgba(255,255,255,0.05); }
        .notif-badge { background: #ff4444; color: white; font-size: 0.6rem; padding: 2px 6px; border-radius: 10px; position: absolute; top: 0; right: 0; }
        .money-nav { color: var(--gold); padding: 10px 15px; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; gap: 5px; }
        .main-container { max-width: 1000px; margin: 20px auto; background: var(--panel); padding: 20px; border-radius: 15px; border: 1px solid #2a2a35; min-height: 400px; }
    </style>
</head>
<body>
<header><h1><i class="fa-solid fa-city"></i> LOVE CITY</h1></header>
<nav>
    <a href="index.php">Главная</a>
    <a href="map.php">Карта</a>
    <a href="friends.php">Друзья <?php if($notif_friends>0) echo "<span class='notif-badge'>$notif_friends</span>"; ?></a>
    <a href="users_list.php">Жители</a>
    <a href="inbox.php">Почта <?php if($notif_msgs>0) echo "<span class='notif-badge' style='background:var(--accent);color:#000'>$notif_msgs</span>"; ?></a>
    <a href="chat.php">Чат</a>
    <a href="profile.php">Профиль</a>
    <?php if(isset($_SESSION['user_id'])): ?>
        <div class="money-nav"><i class="fa-solid fa-coins"></i> <?php echo number_format($my_money); ?> CM</div>
        <a href="logout.php" style="color:#ff4444;">Выход</a>
    <?php endif; ?>
</nav>
<div class="main-container">
