<?php
session_start();
require 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle adding selected exercises
if (isset($_POST['exercise_items'])) {
    foreach ($_POST['exercise_items'] as $exercise_id) {
        $exercise_id = (int)$exercise_id;
        $duration = isset($_POST['duration'][$exercise_id]) ? (int)$_POST['duration'][$exercise_id] : 10; // Default 10 mins
        $duration = max(1, $duration); // Ensure duration is at least 1 min

        // Insert into user_exercise, update duration if exists
        $stmt = $pdo->prepare("INSERT INTO user_exercise (user_id, exercise_id, duration_minutes, date_exercised) 
                               VALUES (:user_id, :exercise_id, :duration, CURDATE()) 
                               ON DUPLICATE KEY UPDATE duration_minutes = duration_minutes + VALUES(duration_minutes)");
        $stmt->execute(['user_id' => $user_id, 'exercise_id' => $exercise_id, 'duration' => $duration]);
    }
    $message = "Exercises added successfully!";
}

// Handle adding a custom exercise
if (isset($_POST['custom_exercise']) && !empty($_POST['custom_exercise_name']) && isset($_POST['custom_calories_per_min'])) {
    $custom_name = trim($_POST['custom_exercise_name']);
    $calories_per_min = (int)$_POST['custom_calories_per_min'];

    if ($calories_per_min <= 0) {
        $message = "Calories burned per minute must be a positive number.";
    } else {
        // Insert custom exercise with the current user's ID so it's only visible to that user
        $stmt = $pdo->prepare("INSERT INTO exercises (exercise_name, calories_burned_per_min, user_id) VALUES (:name, :calories, :user_id)");
        $stmt->execute(['name' => $custom_name, 'calories' => $calories_per_min, 'user_id' => $user_id]);
        $message = "Custom exercise added successfully!";
    }
}

// Check if a search query was submitted
$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Retrieve all exercises (default + user-specific)
// If a search query exists, filter using LIKE
if ($search !== "") {
    $stmt = $pdo->prepare("SELECT exercise_id, exercise_name, calories_burned_per_min 
                           FROM exercises 
                           WHERE (user_id IS NULL OR user_id = 0 OR user_id = :user_id)
                           AND exercise_name LIKE :search");
    $stmt->execute(['user_id' => $user_id, 'search' => '%' . $search . '%']);
} else {
    $stmt = $pdo->prepare("SELECT exercise_id, exercise_name, calories_burned_per_min 
                           FROM exercises 
                           WHERE (user_id IS NULL OR user_id = 0 OR user_id = :user_id)");
    $stmt->execute(['user_id' => $user_id]);
}
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exercise Tracker | Calorie Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ddd; }
        .duration-input { width: 50px; text-align: center; }
        .submit-btn, .add-btn { background-color: green; color: white; padding: 10px 15px; border: none; cursor: pointer; }
        /* Enhanced styling for search bar */
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
        .search-container {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: #222;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .search-container input[type="text"] {
            width: 70%;
            padding: 8px;
            border: 1px solid #333;
            border-radius: 4px;
            margin-right: 10px;
        }
        .search-container button {
            padding: 8px 12px;
            background-color: green;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
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
        <h2>Add Exercise</h2>
        <?php if ($message): ?>
            <p style="color: green;"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Enhanced Search Bar -->
        <div class="search-container">
            <form action="exercise.php" method="GET" style="display: flex; width: 100%;">
                <input type="text" name="search" placeholder="Search exercise..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>
    
    <!-- Exercise Selection Form -->
    <form action="exercise.php" method="POST">
        <table>
            <thead>
                <tr>
                    <th>Select</th>
                    <th>Exercise Name</th>
                    <th>Calories Burned (per min)</th>
                    <th>Duration (mins)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($exercises as $exercise): ?>
                <tr>
                    <td><input type="checkbox" name="exercise_items[]" value="<?php echo $exercise['exercise_id']; ?>"></td>
                    <td><?php echo htmlspecialchars($exercise['exercise_name']); ?></td>
                    <td><?php echo isset($exercise['calories_burned_per_min']) ? $exercise['calories_burned_per_min'] : 'N/A'; ?></td>
                    <td><input type="number" name="duration[<?php echo $exercise['exercise_id']; ?>]" value="10" min="1" class="duration-input"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="submit-btn">Add Selected Exercises</button>
    </form>
    
    <!-- Add Custom Exercise Form -->
    <div class="container">
        <h3>Add Custom Exercise</h3>
        <form action="exercise.php" method="POST">
            <label for="custom_exercise_name">Exercise Name:</label>
            <input type="text" name="custom_exercise_name" required>
            <label for="custom_calories_per_min">Calories Burned (per min):</label>
            <input type="number" name="custom_calories_per_min" step="0.01" required>
            <button type="submit" name="custom_exercise" class="submit-btn">Add Custom Exercise</button>
        </form>
    </div>
</body>
</html>
