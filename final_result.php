<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get calorie limit
$stmt = $pdo->prepare("SELECT calorie_limit FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$calorie_limit = $user ? $user['calorie_limit'] : 0;

// Get total calories consumed today through food items (Updated query)
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(f.calories * uf.quantity), 0) AS total_calories_consumed
    FROM user_food uf
    JOIN food_items f ON uf.food_id = f.food_id
    WHERE uf.user_id = :user_id
      AND uf.date_consumed = CURDATE()
");
$stmt->execute(['user_id' => $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_calories_consumed = $row['total_calories_consumed'];

// Get total calories burned today through exercises
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(e.calories_burned_per_min * ue.duration_minutes), 0) AS total_calories_burned
    FROM user_exercise ue
    JOIN exercises e ON ue.exercise_id = e.exercise_id
    WHERE ue.user_id = :user_id
      AND ue.date_exercised = CURDATE()
");
$stmt->execute(['user_id' => $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_calories_burned = $row['total_calories_burned'];

// Calculate net calories and calories left
$net_calories = $total_calories_consumed - $total_calories_burned;
$calories_left = $calorie_limit - $net_calories;
$goal_met = ($calories_left >= 0) ? 1 : 0;

// Check if a report for today already exists
$stmt = $pdo->prepare("SELECT * FROM final_report WHERE user_id = :user_id AND `date` = CURDATE()");
$stmt->execute(['user_id' => $user_id]);
$existing_report = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_report) {
    // Update the existing report
    $stmt = $pdo->prepare("
        UPDATE final_report 
        SET total_calories_consumed = :total_calories_consumed, 
            total_calories_burned = :total_calories_burned, 
            net_calories = :net_calories, 
            calories_left = :calories_left, 
            goal_met = :goal_met
        WHERE user_id = :user_id AND `date` = CURDATE()
    ");
    $stmt->execute([
        'total_calories_consumed' => $total_calories_consumed,
        'total_calories_burned'   => $total_calories_burned,
        'net_calories'            => $net_calories,
        'calories_left'           => $calories_left,
        'goal_met'                => $goal_met,
        'user_id'                 => $user_id
    ]);
} else {
    // Insert a new report for today
    $stmt = $pdo->prepare("
        INSERT INTO final_report 
            (user_id, total_calories_consumed, total_calories_burned, net_calories, calories_left, goal_met, `date`)
        VALUES 
            (:user_id, :total_calories_consumed, :total_calories_burned, :net_calories, :calories_left, :goal_met, CURDATE())
    ");
    $stmt->execute([
        'user_id'                 => $user_id,
        'total_calories_consumed' => $total_calories_consumed,
        'total_calories_burned'   => $total_calories_burned,
        'net_calories'            => $net_calories,
        'calories_left'           => $calories_left,
        'goal_met'                => $goal_met
    ]);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Result | Calorie Tracker</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Body and container styling */
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
        .result-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #222;
            width: 80%;
            margin: 20px auto;
            border-radius: 5px;
        }
        .result-section p {
            font-size: 18px;
            color: #fff;
            margin-bottom: 15px;
        }
        .result-section hr {
            margin-top: 15px;
        }
        /* Flexbox to place graphs side by side */
        .charts-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;
        }
        .chart-container {
            position: relative;
            width: 48%;
            height: 350px;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="dashboard.php">Set Calories Goal</a>
        <a href="fooditems.php">FoodItems List</a>
        <a href="exercise.php">Add Exercise</a>
        <a href="final_result.php">Final Result</a>
        <a href="history.php">History</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>Final Result</h2>
        <div class="result-section">
            <p><strong>Calorie Limit:</strong> <?php echo $calorie_limit; ?> kcal</p>
            <p><strong>Calories consumed through Food Items:</strong> <?php echo number_format($total_calories_consumed, 2); ?> kcal</p>
            <p><strong>Calories burned through Exercise:</strong> <?php echo number_format($total_calories_burned, 2); ?> kcal</p>
            <p><strong>Total Calories of the Day:</strong> <?php echo number_format($net_calories, 2); ?> kcal</p>
            <p><strong>Calories Left:</strong> 
                <?php 
                    echo ($calories_left >= 0) 
                        ? number_format($calories_left, 2) . " kcal"
                        : "You've exceeded your limit by " . number_format(abs($calories_left), 2) . " kcal!";
                ?>
            </p>
        </div>

        <!-- Flexbox container for charts -->
        <div class="charts-container">
            <!-- Pie chart showing calorie consumption vs. burning -->
            <div class="chart-container">
                <canvas id="caloriePieChart"></canvas>
            </div>
            <!-- Bar chart showing the calories consumed vs. burned -->
            <div class="chart-container">
                <canvas id="calorieBarChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Pie chart for calories consumed vs. burned
        var pieCtx = document.getElementById('caloriePieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Calories Consumed', 'Calories Burned'],
                datasets: [{
                    data: [<?php echo $total_calories_consumed; ?>, <?php echo $total_calories_burned; ?>],
                    backgroundColor: ['rgba(231, 7, 227, 0.4)', 'rgba(44, 13, 223, 0.4)'],
                    borderColor: ['rgba(231, 7, 227, 0.8)', 'rgba(44, 13, 223, 0.8)'],
                    borderWidth: 6,
                    hoverBackgroundColor: ['rgba(231, 7, 227, 0.8)', 'rgba(44, 13, 223, 0.8)'],
                    hoverBorderWidth: 8,
                    hoverBorderColor: ['rgba(255, 255, 255, 1)', 'rgba(255, 255, 255, 1)'],
                    shadowColor: 'rgba(231, 7, 227, 0.8)',
                    shadowBlur: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#e707e3',
                            font: {
                                size: 14,
                                family: 'Arial',
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.9)',
                        titleColor: '#e707e3',
                        bodyColor: '#fff',
                        borderColor: '#e707e3',
                        borderWidth: 2,
                        cornerRadius: 8
                    }
                },
                cutout: '40%'
            }
        });

        // Bar chart for calories consumed vs. burned
        var barCtx = document.getElementById('calorieBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Consumed', 'Burned'],
                datasets: [{
                    data: [<?php echo $total_calories_consumed; ?>, <?php echo $total_calories_burned; ?>],
                    backgroundColor: ['rgba(231, 7, 227, 0.4)', 'rgba(44, 13, 223, 0.4)'],
                    borderColor: ['rgba(231, 7, 227, 0.8)', 'rgba(44, 13, 223, 0.8)'],
                    borderWidth: 6,
                    hoverBackgroundColor: ['rgba(231, 7, 227, 0.8)', 'rgba(44, 13, 223, 0.8)'],
                    hoverBorderWidth: 8,
                    hoverBorderColor: 'rgba(255, 255, 255, 1)',
                    shadowColor: 'rgba(231, 7, 227, 0.8)',
                    shadowBlur: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        ticks: {
                            color: '#e707e3',
                            font: {
                                size: 14,
                                family: 'Arial'
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.2)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#e707e3',
                            font: {
                                size: 14,
                                family: 'Arial'
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.2)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.9)',
                        titleColor: '#e707e3',
                        bodyColor: '#fff',
                        borderColor: '#e707e3',
                        borderWidth: 2,
                        cornerRadius: 8
                    }
                }
            }
        });
    </script>

</body>
</html>
