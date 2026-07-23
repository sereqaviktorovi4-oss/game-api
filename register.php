<?php
session_start();

// Параметры подключения
$host = "localhost"; 
$user = "admin"; 
$pass = "1234"; 
$dbname = "lovesity";

$db = new mysqli($host, $user, $pass, $dbname);
if ($db->connect_error) { die("DB Error"); }
$db->set_charset("utf8mb4");

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $raw_password = $_POST['password'];
    $gender = $_POST['gender'] ?? 'm';
    $year = intval($_POST['year'] ?? 2000);
    $source = $_POST['source'] ?? 'game';

    // 1. Проверка существования пользователя
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "error_exists"; 
    } else {
        // 2. Хеширование пароля
        $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
        
        // 3. Вставка с дефолтным участком '1-1'
        $sql = "INSERT INTO users (username, password, email, gender, birth_year, money, citymoney, plot_coords) 
                VALUES (?, ?, ?, ?, ?, 100, 100, '1-1')";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssssi", $username, $password_hash, $email, $gender, $year);
        
        if ($stmt->execute()) {
            if ($source == 'web') {
                $_SESSION['user_id'] = $db->insert_id;
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
    $stmt->close();
}
$db->close();
?>
