<?php
session_start();
require 'db_connect.php';
require 'vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$showOtpField = false;

if (isset($_POST['send_otp'])) {
    $email = trim($_POST['email']);
    $_SESSION['temp_email'] = $email;

    // Validate email format and existence through AbstractAPI
    $apiKey = '0956680f03424ea18a6e1938b3bf03b8'; // Replace with your AbstractAPI key
    $url = 'https://emailvalidation.abstractapi.com/v1/?api_key=' . $apiKey . '&email=' . urlencode($email);
    
    // Initialize cURL to validate the email
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    curl_close($ch);
    
    // Decode JSON response from AbstractAPI
    $response = json_decode($data, true);
    
    if ($response['deliverability'] != 'DELIVERABLE') {
        $message = "The email address is invalid.";
    } else {
        // Check if email exists in the database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $message = "User with this email already exists.";
        } else {
            // Generate OTP
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;

            // Send OTP email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nellutlaakshith@gmail.com'; 
                $mail->Password = 'beyvvkxm vypa kiko'; // Your App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('nellutlaakshith@gmail.com', 'Calco App');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Your OTP for Registration';
                $mail->Body    = "Your OTP is: <b>$otp</b>";

                $mail->send();
                $message = "OTP sent successfully. Please check your email.";
                $showOtpField = true;
            } catch (Exception $e) {
                $message = "OTP sending failed: " . $mail->ErrorInfo;
            }
        }
    }
} elseif (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $entered_otp = $_POST['otp'];

    // Check OTP first
    if ($entered_otp != $_SESSION['otp']) {
        $message = "Invalid OTP.";
        $showOtpField = true;
    }
    // Validate password strength:
    // At least 8 characters, must contain at least one letter and one number.
    elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
        $message = "Password must be at least 8 characters long and contain at least one letter and one number. Special characters are allowed.";
        $showOtpField = true;
    }
    elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $showOtpField = true;
    } else {
        // Register user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->execute([
            'username' => $username,
            'email' => $_SESSION['temp_email'],
            'password' => $hashed_password
        ]);

        unset($_SESSION['otp'], $_SESSION['temp_email']);
        $message = "Registration successful! You can now login.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register | Calorie Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>REGISTER ACCOUNT</h1>
        <?php if (!empty($message)): ?>
            <p style="color: red;"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if (!$showOtpField): ?>
            <!-- Step 1: Enter Email to Send OTP -->
            <form action="register.php" method="POST">
                <label for="email">Email</label>
                <input type="email" name="email" placeholder="Enter email" required>
                <input type="submit" name="send_otp" value="Send OTP">
            </form>
        <?php else: ?>
            <!-- Step 2: Enter OTP and Details -->
            <form action="register.php" method="POST">
                <label for="otp">Enter OTP</label>
                <input type="text" name="otp" placeholder="Enter OTP" required>

                <label for="username">Username</label>
                <input type="text" name="username" placeholder="Enter username" required>

                <label for="password">Password</label>
                <input type="password" name="password" placeholder="Enter password" required>

                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm password" required>

                <input type="submit" name="register" value="Register Account">
            </form>
        <?php endif; ?>

        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>
