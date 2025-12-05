<?php
session_start();

// Log logout activity
if (isset($_SESSION['user_id'])) {
    require_once 'conn.php';
    $user_id = $_SESSION['user_id'];
    
    // Update session logout time
    if (isset($_SESSION['session_id'])) {
        $session_id = $_SESSION['session_id'];
        $logout_query = "UPDATE user_sessions SET logout_time = NOW() WHERE session_id = $session_id";
        mysqli_query($conn, $logout_query);
    }
    
    // Log activity
    $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                 VALUES ($user_id, 'Logout', 'User logged out')";
    mysqli_query($conn, $log_query);
}

// Clear all session variables
session_unset();
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>
