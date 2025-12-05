<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Handle messages from redirect
if (isset($_GET['message'])) {
    if ($_GET['message'] == 'user_updated') {
        $message = 'User updated successfully';
    } elseif ($_GET['message'] == 'user_deleted') {
        $message = 'User deleted successfully';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'username_exists') {
        $error = 'Username already exists';
    } elseif ($_GET['error'] == 'update_failed') {
        $error = 'Failed to update user';
    } elseif ($_GET['error'] == 'delete_failed') {
        $error = 'Failed to delete user';
    } elseif ($_GET['error'] == 'cannot_delete_self') {
        $error = 'You cannot delete yourself';
    }
}

// Add new user
if (isset($_POST['add_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = hash('sha256', $_POST['password']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $role = $_POST['role'];
    
    $check = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $error = 'Username already exists';
    } else {
        $query = "INSERT INTO users (username, password, fullname, role) 
                 VALUES ('$username', '$password', '$fullname', '$role')";
        if (mysqli_query($conn, $query)) {
            $message = 'User added successfully';
            
            // Log activity
            $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                         VALUES ({$_SESSION['user_id']}, 'Add User', 'Added user: $username')";
            mysqli_query($conn, $log_query);
        } else {
            $error = 'Failed to add user';
        }
    }
}

// Update user status
if (isset($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];
    $new_status = $_GET['status'] == 'Active' ? 'Inactive' : 'Active';
    
    $query = "UPDATE users SET status = '$new_status' WHERE user_id = $user_id";
    if (mysqli_query($conn, $query)) {
        $message = 'User status updated';
        
        $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                     VALUES ({$_SESSION['user_id']}, 'Update User Status', 'Changed user status to $new_status')";
        mysqli_query($conn, $log_query);
    }
    header("Location: users.php");
    exit();
}

// Reset password
if (isset($_POST['reset_password'])) {
    $user_id = (int)$_POST['user_id'];
    $new_password = hash('sha256', $_POST['new_password']);
    
    $query = "UPDATE users SET password = '$new_password' WHERE user_id = $user_id";
    if (mysqli_query($conn, $query)) {
        $message = 'Password reset successfully';
        
        $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                     VALUES ({$_SESSION['user_id']}, 'Reset Password', 'Reset password for user ID: $user_id')";
        mysqli_query($conn, $log_query);
    } else {
        $error = 'Failed to reset password';
    }
}

// Get all users
$users_result = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Inventory System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: #2a5298;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logout-btn {
            background: white;
            color: #2a5298;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
        }
        
        .container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        .sidebar {
            width: 250px;
            background: white;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar a {
            display: block;
            padding: 15px 30px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: #e3f2fd;
            color: #2a5298;
            border-left: 4px solid #2a5298;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .page-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 30px;
        }
        
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #2a5298;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e3c72;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            font-size: 12px;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
            padding: 5px 10px;
            font-size: 11px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            font-size: 11px;
        }
        
        .btn-edit {
            background: #2a5298;
            color: white;
            padding: 5px 10px;
            font-size: 11px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-edit:hover {
            background: #1e3c72;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            color: #333;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge.Active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge.Inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge.Admin {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge.Employee {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
        }
        
        .modal-content h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Inventory Management System</h1>
        <div class="user-info">
            <span>Welcome, <?php echo $_SESSION['fullname']; ?> (Admin)</span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php" class="active">Manage Users</a>
            <a href="products.php">Manage Products</a>
            <a href="reports.php">Reports</a>
            <a href="logs.php">Activity Logs</a>
        </div>
        
        <div class="main-content">
            <h2 class="page-title">Manage Users</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Add New User</h2>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullname" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" required>
                                <option value="Employee">Employee</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Users List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['fullname']; ?></td>
                            <td><span class="badge <?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                            <td><span class="badge <?php echo $user['status']; ?>"><?php echo $user['status']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn-edit" onclick="openEditModal(<?php echo $user['user_id']; ?>, '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['fullname']); ?>', '<?php echo $user['role']; ?>')">
                                    Edit
                                </button>
                                <button class="btn btn-warning" onclick="openResetModal(<?php echo $user['user_id']; ?>, '<?php echo $user['username']; ?>')">
                                    Reset
                                </button>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <a href="deleteA.php?id=<?php echo $user['user_id']; ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                    Delete
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <h3>Reset Password</h3>
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="reset_username" readonly>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                    <button type="button" class="btn btn-secondary" onclick="closeResetModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit User</h3>
            <form method="POST" action="editA.php">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="edit_username" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" id="edit_fullname" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" required>
                        <option value="Employee">Employee</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openResetModal(userId, username) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_username').value = username;
            document.getElementById('resetModal').classList.add('active');
        }
        
        function closeResetModal() {
            document.getElementById('resetModal').classList.remove('active');
        }
        
        function openEditModal(userId, username, fullname, role) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_fullname').value = fullname;
            document.getElementById('edit_role').value = role;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>
</html>
