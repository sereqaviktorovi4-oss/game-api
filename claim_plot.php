<?php
// Вывод ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// --- ЛОГИКА ПОКУПКИ ---
if (isset($_GET['id'])) {
    $coords = $db->real_escape_string($_GET['id']);
    
    // Берем 'money', так как она есть в структуре твоей таблицы
    $user = $db->query("SELECT plot_coords, money FROM users WHERE id=$user_id")->fetch_assoc();
    
    // Проверка на занятость участка
    $check_busy = $db->query("SELECT id FROM users WHERE plot_coords='$coords' AND id != $user_id");
    if ($check_busy->num_rows > 0) {
        die("Этот участок уже кем-то занят!");
    }

    // Первое заселение — бесплатно
    if (empty($user['plot_coords'])) {
        $db->query("UPDATE users SET plot_coords='$coords', plot_name='Первый дом' WHERE id=$user_id");
        header("Location: profile.php");
        exit;
    } else {
        // Покупка второго участка
        $price = 50; 
        $current_money = (int)$user['money'];

        if ($current_money >= $price) {
            // Списываем деньги и ставим новые координаты
            $db->query("UPDATE users SET money = money - $price, plot_coords='$coords' WHERE id=$user_id");
            header("Location: profile.php");
            exit;
        } else {
            // Если денег мало, выводим сколько есть в базе на самом деле
            die("Недостаточно денег! В базе в колонке 'money' у вас: " . $current_money . ". Стоимость участка: " . $price);
        }
    }
} else {
    header("Location: profile.php");
    exit;
}

