<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
// ... дальше твой остальной код
include 'db.php'; 
ini_set('display_errors', 0); 

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ==========================================
// 1. АВТОРИЗАЦИЯ ИГРОКА (LOGIN)
// ==========================================
if ($action == 'login') {
    $username = $db->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $result = $db->query("SELECT * FROM users WHERE username='$username'");
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $db->query("UPDATE users SET platform='game', last_seen=NOW() WHERE id=" . $user['id']);
            
            // Защита координат (float)
            $px = isset($user['pos_x']) ? floatval($user['pos_x']) : 0.0;
            $py = isset($user['pos_y']) ? floatval($user['pos_y']) : 0.0;
            $pz = isset($user['pos_z']) ? floatval($user['pos_z']) : 0.0;
            
            // ЖЕСТКАЯ ПРОВЕРКА УЧАСТКА:
            $raw_dist = trim((string)$user['plot_coords']);
            if (empty($raw_dist) || $raw_dist === '0' || $raw_dist === '0,0,0') {
                $dist = '1-1';
            } else {
                $dist = $raw_dist;
            }

            // Отдаем строку в формате: status:name:id:x:y:z:district:personal_plot
            // Добавили в самый конец $dist (как личный участок), чтобы локальный парсер Godot не путался!
            echo "success:" . $user['username'] . ":" . $user['id'] . ":" . $px . ":" . $py . ":" . $pz . ":" . $dist . ":" . $dist;
        } else { 
            echo "wrong_password"; 
        }
    } else { 
        echo "not_found"; 
    }
}

// ==========================================
// 2. ПОЛУЧЕНИЕ ДАННЫХ ПРОФИЛЯ (ДОБАВЛЕНО!)
// ==========================================
elseif ($action == 'get_profile') {
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        // Выбираем из базы никнейм, статус, пол, личный участок и баланс
        $result = $db->query("SELECT username, status_text, gender, plot_coords, money FROM users WHERE id = $user_id");
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Чистим координаты участка
            $raw_plot = trim((string)$user['plot_coords']);
            $plot = (empty($raw_plot) || $raw_plot === '0' || $raw_plot === '0,0,0') ? "1-1" : $raw_plot;
            
            // Формируем JSON ответ для Godot
            $response = [
                "status" => "success",
                "username" => $user['username'],
                "status_text" => !empty($user['status_text']) ? $user['status_text'] : "Я здесь новый житель!",
                "gender" => !empty($user['gender']) ? $user['gender'] : "m",
                "district_name" => "Love Sector A", // Название района по умолчанию
                "plot_coords" => $plot,
                "citymoney" => intval($user['money'] ?? 100)
            ];
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["status" => "error", "message" => "user_not_found"]);
}

// ==========================================
// 3. СОХРАНЕНИЕ ПОСТРОЙКИ
// ==========================================
elseif ($action == 'save_build') {
    $user_id = (int)$_POST['user_id'];
    $x = (float)$_POST['x'];
    $y = (float)$_POST['y'];
    $z = (float)$_POST['z'];
    $shape = $db->real_escape_string($_POST['shape']);
    $color = $db->real_escape_string($_POST['color']);
    $tex = $db->real_escape_string($_POST['texture']);
    $dist = $db->real_escape_string($_POST['district']);

    $db->query("INSERT INTO builds (user_id, pos_x, pos_y, pos_z, shape_type, color_hex, texture_url, district) 
                VALUES ($user_id, $x, $y, $z, '$shape', '$color', '$tex', '$dist')");
    echo "saved";
}

// ==========================================
// 4. ЗАГРУЗКА ВСЕХ ПОСТРОЕК
// ==========================================
elseif ($action == 'load_all_builds') {
    $res = $db->query("SELECT * FROM builds");
    $builds = [];
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $builds[] = $row;
        }
    }
    header('Content-Type: application/json');
    echo json_encode($builds);
}

// ==========================================
// 5. ДАННЫЕ УЧАСТКА
// ==========================================
elseif ($action == 'get_land') {
    $name = $db->real_escape_string($_GET['name'] ?? '');
    $res = $db->query("SELECT pos_x, pos_y, pos_z, plot_coords FROM users WHERE username='$name'");
    $data = $res->fetch_assoc();

    if ($data) {
        $lx = (float)($data['pos_x'] ?? 0.0);
        $ly = (float)($data['pos_y'] ?? 0.0);
        $lz = (float)($data['pos_z'] ?? 0.0);
        
        $raw_dist = trim((string)($data['plot_coords'] ?? ''));
        $ldist = (empty($raw_dist) || $raw_dist === '0' || $raw_dist === '0,0,0') ? '1-1' : $raw_dist;

        header('Content-Type: application/json');
        echo json_encode([
            "status" => "success",
            "x" => $lx,
            "y" => $ly,
            "z" => $lz,
            "district" => $ldist
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "not_found"]);
    }
}

$db->close();
?>
