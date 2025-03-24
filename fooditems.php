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

// Handle adding selected food items
if (isset($_POST['food_items'])) {
    foreach ($_POST['food_items'] as $food_id) {
        $food_id = (int)$food_id;
        $quantity = isset($_POST['quantity'][$food_id]) ? (int)$_POST['quantity'][$food_id] : 1;
        $quantity = max(1, $quantity); // Ensure quantity is at least 1

        // Insert into user_food, update quantity if exists
        $stmt = $pdo->prepare("INSERT INTO user_food (user_id, food_id, quantity, date_consumed) 
        VALUES (:user_id, :food_id, :quantity, CURDATE()) 
        ON DUPLICATE KEY UPDATE quantity = quantity + :quantity");
$stmt->execute(['user_id' => $user_id, 'food_id' => $food_id, 'quantity' => $quantity]);

    }
    $message = "Food items added successfully!";
}

// Handle adding a custom food item
if (isset($_POST['add_custom_food'])) {
    $food_name = trim($_POST['food_name']);
    $calories = (int)$_POST['calories'];
    $carbs = (int)$_POST['carbs'];
    $fats = (int)$_POST['fats'];
    $proteins = (int)$_POST['proteins'];

    if (!empty($food_name) && $calories >= 0 && $carbs >= 0 && $fats >= 0 && $proteins >= 0) {
        // Insert custom food with user_id so it's only visible to the current user
        $stmt = $pdo->prepare("INSERT INTO food_items (food_name, calories, carbs, fats, proteins, user_id) 
                               VALUES (:food_name, :calories, :carbs, :fats, :proteins, :user_id)");
        $stmt->execute([
            'food_name' => $food_name,
            'calories' => $calories,
            'carbs' => $carbs,
            'fats' => $fats,
            'proteins' => $proteins,
            'user_id' => $user_id
        ]);
        $message = "Custom food item added successfully!";
    } else {
        $message = "Please fill in all fields correctly.";
    }
}

// Check if a search query was submitted
$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Retrieve food items (default items: user_id IS NULL or 0, plus user's custom items)
// If a search query exists, filter using LIKE
if ($search !== "") {
    $stmt = $pdo->prepare("SELECT food_id, food_name, calories, carbs, fats, proteins 
                           FROM food_items 
                           WHERE (user_id IS NULL OR user_id = 0 OR user_id = :user_id)
                           AND food_name LIKE :search");
    $stmt->execute(['user_id' => $user_id, 'search' => '%' . $search . '%']);
} else {
    $stmt = $pdo->prepare("SELECT food_id, food_name, calories, carbs, fats, proteins 
                           FROM food_items 
                           WHERE (user_id IS NULL OR user_id = 0 OR user_id = :user_id)");
    $stmt->execute(['user_id' => $user_id]);
}
$foodItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Food Items | Calorie Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ddd; }
        .quantity-input { width: 50px; text-align: center; }
        .submit-btn, .add-btn { background-color: green; color: white; padding: 10px 15px; border: none; cursor: pointer; }
        .popup { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                 background: white; padding: 20px; border: 1px solid #ccc; box-shadow: 0 0 10px #999; }
        .popup input { display: block; width: 90%; margin: 10px 0; padding: 5px; }
        .close-btn { background-color: red; padding: 5px 10px; margin-top: 10px; color: white; cursor: pointer; }
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
        <a href="fooditems.php">FoodItems List</a>
        <a href="exercise.php">Add Exercise</a>
        <a href="final_result.php">Final Result</a>
        <a href="history.php">History</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="container">
        <h2>Food Items</h2>
        <?php if ($message): ?>
            <p style="color: green;"><?php echo $message; ?></p>
        <?php endif; ?>
        <button class="add-btn" onclick="document.getElementById('customFoodPopup').style.display='block'">+ Add Custom Food</button>
        
        <!-- Enhanced Search Bar -->
        <div class="search-container">
            <form action="fooditems.php" method="GET" style="display: flex; width: 100%;">
                <input type="text" name="search" placeholder="Search food item..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>
    
    <!-- Custom Food Popup Form -->
    <div id="customFoodPopup" class="popup">
        <h3>Add Custom Food Item</h3>
        <form action="fooditems.php" method="POST">
            <input type="text" name="food_name" placeholder="Food Name" required>
            <input type="number" name="calories" placeholder="Calories" min="0" required>
            <input type="number" name="carbs" placeholder="Carbs" min="0" required>
            <input type="number" name="fats" placeholder="Fats" min="0" required>
            <input type="number" name="proteins" placeholder="Proteins" min="0" required>
            <button type="submit" name="add_custom_food" class="submit-btn">Add</button>
            <button type="button" class="close-btn" onclick="document.getElementById('customFoodPopup').style.display='none'">Close</button>
        </form>
    </div>
    
    <!-- Food Items Selection Form -->
    <form action="fooditems.php" method="POST">
        <table>
            <thead>
                <tr>
                    <th>Select</th>
                    <th>Food Name</th>
                    <th>Calories</th>
                    <th>Carbs</th>
                    <th>Fats</th>
                    <th>Proteins</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($foodItems as $food): ?>
                <tr>
                    <td><input type="checkbox" name="food_items[]" value="<?php echo $food['food_id']; ?>"></td>
                    <td><?php echo htmlspecialchars($food['food_name']); ?></td>
                    <td><?php echo $food['calories']; ?></td>
                    <td><?php echo $food['carbs']; ?></td>
                    <td><?php echo $food['fats']; ?></td>
                    <td><?php echo $food['proteins']; ?></td>
                    <td><input type="number" name="quantity[<?php echo $food['food_id']; ?>]" value="1" min="1" class="quantity-input"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="submit-btn">Add Selected Items</button>
    </form>


</body>
</html>
