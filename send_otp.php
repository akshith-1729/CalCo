<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // PHPMailer

$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com';
    $mail->Password = 'your-app-password'; // Use App Password, NOT regular password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Email Content
    $mail->setFrom('your-email@gmail.com', 'Calco App');
    $mail->addAddress($_SESSION['email']);
    $mail->Subject = "Your OTP Code";
    $mail->Body = "Your OTP is: " . $_SESSION['otp'] . ". It is valid for 5 minutes.";

    $mail->send();
} catch (Exception $e) {
    echo "OTP sending failed: " . $mail->ErrorInfo;
}
