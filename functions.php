<?php
// functions.php
function update_streak($user_id, $pdo) {
    $today = date('Y-m-d');

    // Fetch the most recent streak record for the user
    $stmt = $pdo->prepare("SELECT * FROM user_streaks WHERE user_id = :user_id ORDER BY streak_end_date DESC LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $last_streak = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no streak exists, create a new streak
    if (!$last_streak) {
        $stmt = $pdo->prepare("INSERT INTO user_streaks (user_id, streak_start_date, streak_end_date, streak_count) 
                               VALUES (:user_id, :today, :today, 1)");
        $stmt->execute(['user_id' => $user_id, 'today' => $today]);
        return;
    }

    $last_streak_date = $last_streak['streak_end_date'];
    $last_streak_count = $last_streak['streak_count'];

    // Check if the streak continues (previous streak was yesterday)
    if (date('Y-m-d', strtotime($last_streak_date . ' +1 day')) === $today) {
        // Streak continues, update the streak
        $stmt = $pdo->prepare("UPDATE user_streaks 
                               SET streak_end_date = :today, streak_count = :count 
                               WHERE user_id = :user_id AND streak_end_date = :last_streak_date");
        $stmt->execute(['user_id' => $user_id, 'today' => $today, 'count' => $last_streak_count + 1, 'last_streak_date' => $last_streak_date]);
    } else {
        // Streak is broken, create a new streak
        $stmt = $pdo->prepare("INSERT INTO user_streaks (user_id, streak_start_date, streak_end_date, streak_count) 
                               VALUES (:user_id, :today, :today, 1)");
        $stmt->execute(['user_id' => $user_id, 'today' => $today]);
    }
}

?>