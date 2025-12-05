<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../index.php");
    exit();
}

$logs_query = "SELECT al.*, u.fullname, u.username 
               FROM activity_logs al 
               JOIN users u ON al.user_id = u.user_id 
               ORDER BY al.created_at DESC 
               LIMIT 100";
$logs_result = mysqli_query($conn, $logs_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Inventory System</title>
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
        .main-content { flex: 1; padding: 30px; }
        .page-title { font-size: 28px; color: #333; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card h2 { color: #333; margin-bottom: 20px; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #333; }
        table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        table tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: 500; }
        .badge.login { background: #d4edda; color: #155724; }
        .badge.logout { background: #fff3cd; color: #856404; }
        .badge.add { background: #d1ecf1; color: #0c5460; }
        .badge.update { background: #cce5ff; color: #004085; }
        .badge.delete { background: #f8d7da; color: #721c24; }
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
            <a href="users.php">Manage Users</a>
            <a href="products.php">Manage Products</a>
            <a href="reports.php">Reports</a>
            <a href="logs.php" class="active">Activity Logs</a>
        </div>
        
        <div class="main-content">
            <h2 class="page-title">Activity Logs</h2>
            
            <div class="card">
                <h2>System Activity History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Username</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = mysqli_fetch_assoc($logs_result)): ?>
                        <tr>
                            <td><?php echo $log['log_id']; ?></td>
                            <td><?php echo $log['fullname']; ?></td>
                            <td><?php echo $log['username']; ?></td>
                            <td>
                                <?php 
                                $action_class = 'add';
                                if (stripos($log['action'], 'login') !== false) $action_class = 'login';
                                if (stripos($log['action'], 'logout') !== false) $action_class = 'logout';
                                if (stripos($log['action'], 'update') !== false) $action_class = 'update';
                                if (stripos($log['action'], 'delete') !== false) $action_class = 'delete';
                                ?>
                                <span class="badge <?php echo $action_class; ?>">
                                    <?php echo $log['action']; ?>
                                </span>
                            </td>
                            <td><?php echo $log['description']; ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
