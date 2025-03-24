<?php
// login.php
session_start();
require 'db_connect.php';

$message = "";


if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Please fill out all fields.";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['calorie_limit'] = $user['calorie_limit'];
            header("Location: dashboard.php");
            exit;
        } else {
            $message = "Invalid username or password.";
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Login | Calorie Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>LOGIN</h1>
        <?php if (!empty($message)): ?>
            <p style="color: red;"><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" placeholder="Enter username" required>

            <label for="password">Password</label>
            <input type="password" name="password" placeholder="Enter password" required>

            <input type="submit" name="login" value="Login">
        </form>
        <p>Don't have an account? <a href="register.php">Sign Up</a></p>
    </div>
</body>
</html>
