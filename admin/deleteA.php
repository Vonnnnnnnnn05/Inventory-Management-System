<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    // Prevent deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        header("Location: users.php?error=cannot_delete_self");
        exit();
    }
    
    // Get username before deleting
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE user_id = $user_id"));
    
    $query = "DELETE FROM users WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $query)) {
        // Log activity
        $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                     VALUES ({$_SESSION['user_id']}, 'Delete User', 'Deleted user: {$user['username']}')";
        mysqli_query($conn, $log_query);
        
        header("Location: users.php?message=user_deleted");
    } else {
        header("Location: users.php?error=delete_failed");
    }
} else {
    header("Location: users.php");
}
exit();
?>
