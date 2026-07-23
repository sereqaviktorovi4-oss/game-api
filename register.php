<?php
session_start();
include 'db.php';

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = pg_escape_string($db, trim($_POST['username']));
    $email = pg_escape_string($db, trim($_POST['email'] ?? ''));
    $raw_password = $_POST['password'];
    $gender = pg_escape_string($db, $_POST['gender'] ?? 'm');
    $year = intval($_POST['year'] ?? 2000);
    $source = $_POST['source'] ?? 'game';

    // 1. Проверка существования пользователя (по никнейму или email)
    $check_query = "SELECT id FROM users WHERE username = '$username'";
    if (!empty($email)) {
        $check_query .= " OR email = '$email'";
    }
    
    $result = pg_query($db, $check_query);

    if ($result && pg_num_rows($result) > 0) {
        echo "error_exists"; 
    } else {
        // 2. Хеширование пароля
        $password_hash = pg_escape_string($db, password_hash($raw_password, PASSWORD_DEFAULT));
        
        // 3. Вставка с дефолтным участком '1-1'
        $sql = "INSERT INTO users (username, password, email, gender, birth_year, money, citymoney, plot_coords) 
                VALUES ('$username', '$password_hash', '$email', '$gender', $year, 100, 100, '1-1') RETURNING id";
        
        $insert_result = pg_query($db, $sql);
        
        if ($insert_result) {
            if ($source == 'web') {
                $row = pg_fetch_assoc($insert_result);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                header("Location: index.php"); 
                exit;
            } else { 
                echo "success"; 
            }
        } else { 
            echo "error_db"; 
        }
    }
}

pg_close($db);
?>
