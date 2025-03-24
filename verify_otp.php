<?php
session_start();
require 'db_connect.php';

$message = "";

if (isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];

    if (!isset($_SESSION['otp']) || !isset($_SESSION['email'])) {
        $message = "Session expired. Please request a new OTP.";
    } elseif (time() > $_SESSION['otp_expiry']) {
        $message = "OTP expired. Please request a new one.";
        unset($_SESSION['otp']);
    } elseif ($entered_otp == $_SESSION['otp']) {
        // OTP is correct - Insert user into DB
        $email = $_SESSION['email'];
        $password = password_hash($_SESSION['password'], PASSWORD_DEFAULT);
        $username = $_SESSION['username'];

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) 
                               VALUES (:username, :email, :password)");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);

        unset($_SESSION['otp'], $_SESSION['email'], $_SESSION['password'], $_SESSION['username']);

        $message = "Registration successful! You can now login.";
    } else {
        $message = "Invalid OTP. Try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP | Calorie Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>VERIFY OTP</h1>
        <?php if (!empty($message)): ?>
            <p style="color: red;"><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="verify_otp.php" method="POST">
            <label for="otp">Enter OTP</label>
            <input type="text" name="otp" placeholder="Enter OTP" required>
            <input type="submit" name="verify_otp" value="Verify OTP">
        </form>
    </div>
</body>
</html>
