<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Employee') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Handle messages from redirect
if (isset($_GET['message'])) {
    if ($_GET['message'] == 'profile_updated') {
        $message = 'Profile updated successfully';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'update_failed') {
        $error = 'Failed to update profile';
    }
}

// Change password
if (isset($_POST['change_password'])) {
    $current_password = hash('sha256', $_POST['current_password']);
    $new_password = hash('sha256', $_POST['new_password']);
    $confirm_password = hash('sha256', $_POST['confirm_password']);
    
    // Verify current password
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE user_id = {$_SESSION['user_id']}"));
    
    if ($current_password === $user['password']) {
        if ($new_password === $confirm_password) {
            $query = "UPDATE users SET password = '$new_password' WHERE user_id = {$_SESSION['user_id']}";
            if (mysqli_query($conn, $query)) {
                $message = 'Password changed successfully';
                
                $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                             VALUES ({$_SESSION['user_id']}, 'Change Password', 'User changed their password')";
                mysqli_query($conn, $log_query);
            } else {
                $error = 'Failed to change password';
            }
        } else {
            $error = 'New passwords do not match';
        }
    } else {
        $error = 'Current password is incorrect';
    }
}

// Get user info
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id = {$_SESSION['user_id']}"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Inventory System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #2a5298; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .logout-btn { background: white; color: #2a5298; padding: 8px 20px; border-radius: 5px; text-decoration: none; font-weight: 500; }
        .container { display: flex; min-height: calc(100vh - 60px); }
        .sidebar { width: 250px; background: white; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar a { display: block; padding: 15px 30px; color: #333; text-decoration: none; transition: background 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #e3f2fd; color: #2a5298; border-left: 4px solid #2a5298; }
        .main-content { flex: 1; padding: 30px; max-width: 800px; }
        .page-title { font-size: 28px; color: #333; margin-bottom: 30px; }
        .message { padding: 12px; margin-bottom: 20px; border-radius: 5px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card h2 { color: #333; margin-bottom: 20px; font-size: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #333; margin-bottom: 5px; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-primary { background: #2a5298; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; }
        .btn-primary:hover { background: #1e3c72; }
        .info-row { display: flex; margin-bottom: 15px; }
        .info-row label { width: 150px; font-weight: 500; color: #666; }
        .info-row span { color: #333; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Inventory Management System</h1>
        <div class="user-info">
            <span>Welcome, <?php echo $_SESSION['fullname']; ?> (Employee)</span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <a href="dashboard.php">Dashboard</a>
            <a href="stock.php">Stock Management</a>
            <a href="reports.php">View Reports</a>
            <a href="profile.php" class="active">My Profile</a>
        </div>
        
        <div class="main-content">
            <h2 class="page-title">My Profile</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Profile Information</h2>
                <div class="info-row">
                    <label>User ID:</label>
                    <span><?php echo $user['user_id']; ?></span>
                </div>
                <div class="info-row">
                    <label>Username:</label>
                    <span><?php echo $user['username']; ?></span>
                </div>
                <div class="info-row">
                    <label>Role:</label>
                    <span><?php echo $user['role']; ?></span>
                </div>
                <div class="info-row">
                    <label>Status:</label>
                    <span><?php echo $user['status']; ?></span>
                </div>
                <div class="info-row">
                    <label>Created:</label>
                    <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="card">
                <h2>Edit Profile</h2>
                <form method="POST" action="editE.php">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" value="<?php echo $user['fullname']; ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Change Password</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
