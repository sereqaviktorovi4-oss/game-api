<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; 

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ==========================================
// 1. АВТОРИЗАЦИЯ ИГРОКА (LOGIN)
// ==========================================
if ($action == 'login') {
    $username = pg_escape_string($db, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = pg_query($db, "SELECT * FROM users WHERE username='$username'");
    if ($result && pg_num_rows($result) > 0) {
        $user = pg_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            pg_query($db, "UPDATE users SET platform='game', last_seen=NOW() WHERE id=" . intval($user['id']));
            
            // Защита координат (float)
            $px = isset($user['pos_x']) ? floatval($user['pos_x']) : 0.0;
            $py = isset($user['pos_y']) ? floatval($user['pos_y']) : 0.0;
            $pz = isset($user['pos_z']) ? floatval($user['pos_z']) : 0.0;
            
            // ЖЕСТКАЯ ПРОВЕРКА УЧАСТКА:
            $raw_dist = trim((string)($user['plot_coords'] ?? ''));
            if (empty($raw_dist) || $raw_dist === '0' || $raw_dist === '0,0,0') {
                $dist = '1-1';
            } else {
                $dist = $raw_dist;
            }

            // Отдаем строку в формате: status:name:id:x:y:z:district:personal_plot
            echo "success:" . $user['username'] . ":" . $user['id'] . ":" . $px . ":" . $py . ":" . $pz . ":" . $dist . ":" . $dist;
        } else { 
            echo "wrong_password"; 
        }
    } else { 
        echo "not_found"; 
    }
}

// ==========================================
// 2. ПОЛУЧЕНИЕ ДАННЫХ ПРОФИЛЯ
// ==========================================
elseif ($action == 'get_profile') {
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        $result = pg_query($db, "SELECT username, status_text, gender, plot_coords, money FROM users WHERE id = $user_id");
        if ($result && pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            
            $raw_plot = trim((string)($user['plot_coords'] ?? ''));
            $plot = (empty($raw_plot) || $raw_plot === '0' || $raw_plot === '0,0,0') ? "1-1" : $raw_plot;
            
            $response = [
                "status" => "success",
                "username" => $user['username'],
                "status_text" => !empty($user['status_text']) ? $user['status_text'] : "Я здесь новый житель!",
                "gender" => !empty($user['gender']) ? $user['gender'] : "m",
                "district_name" => "Love Sector A",
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
    $user_id = intval($_POST['user_id'] ?? 0);
    $x = floatval($_POST['x'] ?? 0);
    $y = floatval($_POST['y'] ?? 0);
    $z = floatval($_POST['z'] ?? 0);
    $shape = pg_escape_string($db, $_POST['shape'] ?? '');
    $color = pg_escape_string($db, $_POST['color'] ?? '');
    $tex = pg_escape_string($db, $_POST['texture'] ?? '');
    $dist = pg_escape_string($db, $_POST['district'] ?? '');

    pg_query($db, "INSERT INTO builds (user_id, pos_x, pos_y, pos_z, shape_type, color_hex, texture_url, district) 
                VALUES ($user_id, $x, $y, $z, '$shape', '$color', '$tex', '$dist')");
    echo "saved";
}

// ==========================================
// 4. ЗАГРУЗКА ВСЕХ ПОСТРОЕК
// ==========================================
elseif ($action == 'load_all_builds') {
    $res = pg_query($db, "SELECT * FROM builds");
    $builds = [];
    if ($res) {
        while($row = pg_fetch_assoc($res)) {
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
    $name = pg_escape_string($db, $_GET['name'] ?? '');
    $res = pg_query($db, "SELECT pos_x, pos_y, pos_z, plot_coords FROM users WHERE username='$name'");
    
    if ($res && pg_num_rows($res) > 0) {
        $data = pg_fetch_assoc($res);
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

pg_close($db);
?>
