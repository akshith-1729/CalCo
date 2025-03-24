<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch calorie history
$stmt = $pdo->prepare("
    SELECT date, total_calories_consumed, total_calories_burned, net_calories, calories_left, goal_met
    FROM final_report
    WHERE user_id = :user_id
    ORDER BY date DESC
");
$stmt->execute(['user_id' => $user_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calorie History | Calorie Tracker</title>
    <link rel="stylesheet" href="style.css">
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
            color: #f00;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #222;
            border-radius: 5px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #333;
            text-align: center;
        }
        th {
            background-color: #333;
            color: #f00;
        }
        .goal-met {
            color: #0f0;
            font-weight: bold;
        }
        .goal-not-met {
            color: #f00;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="dashboard.php">Set Calories Goal</a>
        <a href="fooditems.php">FoodItems List</a>
        <a href="exercise.php">Add Exercise</a>
        <a href="final_result.php">Final Result</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>Calorie History</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Calories Consumed</th>
                <th>Calories Burned</th>
                <th>Net Calories</th>
                <th>Calories Left</th>
                <th>Goal Met?</th>
            </tr>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo number_format($row['total_calories_consumed'], 2); ?> kcal</td>
                    <td><?php echo number_format($row['total_calories_burned'], 2); ?> kcal</td>
                    <td><?php echo number_format($row['net_calories'], 2); ?> kcal</td>
                    <td>
                        <?php echo ($row['calories_left'] >= 0) 
                            ? number_format($row['calories_left'], 2) . " kcal" 
                            : "Exceeded by " . number_format(abs($row['calories_left']), 2) . " kcal"; 
                        ?>
                    </td>
                    <td class="<?php echo ($row['goal_met']) ? 'goal-met' : 'goal-not-met'; ?>">
                        <?php echo ($row['goal_met']) ? "✔ Yes" : "❌ No"; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

</body>
</html>
