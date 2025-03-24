<?php
// db_connect.php
$host = "localhost";    // or "127.0.0.1"
$dbname = "calorie_tracker";
$username = "root";     // change if needed
$password = "";         // change if needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Enable exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>
