<?php
session_start();
require 'db_connect.php'; // Include your DB connection

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's streak data (latest 10 streaks)
$stmt = $pdo->prepare("SELECT * FROM user_streaks WHERE user_id = :user_id ORDER BY streak_end_date DESC LIMIT 10");
$stmt->execute(['user_id' => $user_id]);
$streaks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Streaks | Calorie Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .streak-container {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }

        .streak-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .streak-container table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        .streak-container th, .streak-container td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .streak-container th {
            background-color: #f2f2f2;
        }

        .navbar {
            background-color: #222;
            padding: 10px;
            text-align: center;
        }

        .navbar a {
            margin: 0 10px;
            color: #fff;
        }

        .navbar a:hover {
            color: #f00;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="exercise.php">Exercise</a>
        <a href="streaks_display.php">Your Streaks</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="streak-container">
        <h2>Your Streaks</h2>
        <table>
            <thead>
                <tr>
                    <th>Streak Start Date</th>
                    <th>Streak End Date</th>
                    <th>Streak Days</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($streaks as $streak): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($streak['streak_start_date']); ?></td>
                        <td><?php echo htmlspecialchars($streak['streak_end_date']); ?></td>
                        <td><?php echo htmlspecialchars($streak['streak_count']); ?> days</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
