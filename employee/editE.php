<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Employee') {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'];
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    
    $query = "UPDATE users SET fullname = '$fullname' WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $query)) {
        // Update session
        $_SESSION['fullname'] = $fullname;
        
        // Log activity
        $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                     VALUES ($user_id, 'Update Profile', 'Updated profile information')";
        mysqli_query($conn, $log_query);
        
        header("Location: profile.php?message=profile_updated");
    } else {
        header("Location: profile.php?error=update_failed");
    }
} else {
    header("Location: profile.php");
}
exit();
?>
