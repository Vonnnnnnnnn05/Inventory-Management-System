<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['edit_user'])) {
    $user_id = (int)$_POST['user_id'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $role = $_POST['role'];
    
    // Check if username already exists for other users
    $check = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' AND user_id != $user_id");
    if (mysqli_num_rows($check) > 0) {
        header("Location: users.php?error=username_exists");
        exit();
    }
    
    $query = "UPDATE users SET username = '$username', fullname = '$fullname', role = '$role' WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $query)) {
        // Log activity
        $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                     VALUES ({$_SESSION['user_id']}, 'Edit User', 'Updated user: $username')";
        mysqli_query($conn, $log_query);
        
        header("Location: users.php?message=user_updated");
    } else {
        header("Location: users.php?error=update_failed");
    }
} else {
    header("Location: users.php");
}
exit();
?>
