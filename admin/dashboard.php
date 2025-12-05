<?php
session_start();
require_once '../conn.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Get dashboard statistics
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_transactions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM inventory_transactions WHERE DATE(created_at) = CURDATE()"))['count'];

// Get recent activity logs
$logs_query = "SELECT al.*, u.fullname FROM activity_logs al 
               JOIN users u ON al.user_id = u.user_id 
               ORDER BY al.created_at DESC LIMIT 5";
$logs_result = mysqli_query($conn, $logs_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Inventory System</title>
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
        
        .logout-btn:hover {
            background: #f0f0f0;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #2a5298;
        }
        
        .stat-card.warning .number {
            color: #ff9800;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .activity-log {
            list-style: none;
        }
        
        .activity-log li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-log li:last-child {
            border-bottom: none;
        }
        
        .activity-log .user {
            font-weight: 500;
            color: #2a5298;
        }
        
        .activity-log .time {
            color: #999;
            font-size: 12px;
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
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="users.php">Manage Users</a>
            <a href="products.php">Manage Products</a>
            <a href="reports.php">Reports</a>
            <a href="logs.php">Activity Logs</a>
        </div>
        
        <div class="main-content">
            <h2 class="page-title">Dashboard Overview</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="number"><?php echo $total_products; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="number"><?php echo $total_users; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Today's Transactions</h3>
                    <div class="number"><?php echo $total_transactions; ?></div>
                </div>
            </div>
            
            <div class="card">
                <h2>Recent Activity</h2>
                <ul class="activity-log">
                    <?php while ($log = mysqli_fetch_assoc($logs_result)): ?>
                        <li>
                            <span class="user"><?php echo $log['fullname']; ?></span> - <?php echo $log['action']; ?>
                            <div class="time"><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
