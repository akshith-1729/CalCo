<?php
// dashboard.php
session_start();
require 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$message = "";

if (isset($_POST['set_limit'])) {
    $calorie_limit = (int)$_POST['calorie_limit'];

    // Ensure calorie limit is a positive number
    if ($calorie_limit <= 0) {
        $message = "Calorie limit must be a positive number.";
    } else {
        // ✅ FIXED: Changed `id` to `user_id`
        $stmt = $pdo->prepare("UPDATE users SET calorie_limit = :calorie_limit WHERE user_id = :user_id");
        $stmt->execute(['calorie_limit' => $calorie_limit, 'user_id' => $user_id]);

        // Reset today's food and exercise data
        $stmt = $pdo->prepare("DELETE FROM user_food WHERE user_id = :user_id AND date_consumed = CURDATE()");
        $stmt->execute(['user_id' => $user_id]);

        $stmt = $pdo->prepare("DELETE FROM user_exercise WHERE user_id = :user_id AND date_exercised = CURDATE()");
        $stmt->execute(['user_id' => $user_id]);

        // Update session variable
        $_SESSION['calorie_limit'] = $calorie_limit;

        $message = "Calorie limit updated to $calorie_limit. Today's food and exercise data have been reset!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Calorie Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <a href="dashboard.php">Set Calories Goal</a>
        <a href="fooditems.php">Food Items List</a>
        <a href="exercise.php">Add Exercise</a>
        <a href="final_result.php">Final Result</a>
        <a href="history.php">History</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <?php if (!empty($message)): ?>
            <p style="color: <?php echo strpos($message, 'must be a positive number') !== false ? 'red' : 'green'; ?>">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <form action="dashboard.php" method="POST">
            <label for="calorie_limit">Enter your Calorie Limit:</label>
            <input type="number" name="calorie_limit" min="1" value="<?php echo isset($_SESSION['calorie_limit']) ? $_SESSION['calorie_limit'] : 2000; ?>" required>
            <input type="submit" name="set_limit" value="Submit">
        </form>
    </div>

    <style>
        body {
            background-color: #1a1a1a;
            color: #fff;
            font-family: Arial, sans-serif;
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
        .container {
            width: 80%;
            margin: 40px auto;
            padding: 20px;
            background-color: #111;
            border-radius: 5px;
            text-align: center;
        }
        h2 {
            color: #f00; /* Red Heading */
            margin-bottom: 20px;
        }
        form input {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #444;
            background-color: #333;
            color: #fff;
            border-radius: 5px;
        }
        form input[type="submit"] {
            background-color:rgba(0,128,0,255);
            cursor: pointer;
        }
        form input[type="submit"]:hover {
            background-color:rgb(102, 213, 22);
        }
    </style>
</body>
</html>
