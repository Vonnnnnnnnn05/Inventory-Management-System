<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Employee') {
    header("Location: ../index.php");
    exit();
}

$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$my_transactions_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM inventory_transactions WHERE user_id = {$_SESSION['user_id']} AND DATE(created_at) = CURDATE()"))['count'];

$transactions_query = "SELECT t.*, p.product_name 
                       FROM inventory_transactions t 
                       JOIN products p ON t.product_id = p.product_id 
                       WHERE t.user_id = {$_SESSION['user_id']} 
                       ORDER BY t.created_at DESC 
                       LIMIT 5";
$transactions_result = mysqli_query($conn, $transactions_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Inventory System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #2a5298; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .logout-btn { background: white; color: #2a5298; padding: 8px 20px; border-radius: 5px; text-decoration: none; font-weight: 500; }
        .logout-btn:hover { background: #f0f0f0; }
        .container { display: flex; min-height: calc(100vh - 60px); }
        .sidebar { width: 250px; background: white; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar a { display: block; padding: 15px 30px; color: #333; text-decoration: none; transition: background 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #e3f2fd; color: #2a5298; border-left: 4px solid #2a5298; }
        .main-content { flex: 1; padding: 30px; }
        .page-title { font-size: 28px; color: #333; margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #666; font-size: 14px; font-weight: 500; margin-bottom: 10px; }
        .stat-card .number { font-size: 36px; font-weight: bold; color: #2a5298; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card h2 { color: #333; margin-bottom: 20px; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #333; }
        table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        table tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: 500; }
        .badge.stock-in { background: #d4edda; color: #155724; }
        .badge.stock-out { background: #fff3cd; color: #856404; }
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
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="stock.php">Stock Management</a>
            <a href="reports.php">View Reports</a>
            <a href="profile.php">My Profile</a>
        </div>
        
        <div class="main-content">
            <h2 class="page-title">Dashboard Overview</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="number"><?php echo $total_products; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>My Transactions Today</h3>
                    <div class="number"><?php echo $my_transactions_today; ?></div>
                </div>
            </div>
            
            <div class="card">
                <h2>My Recent Transactions</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Remarks</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($transactions_result) > 0):
                            while ($trans = mysqli_fetch_assoc($transactions_result)): 
                        ?>
                        <tr>
                            <td><?php echo $trans['product_name']; ?></td>
                            <td>
                                <span class="badge <?php echo strtolower(str_replace(' ', '-', $trans['transaction_type'])); ?>">
                                    <?php echo $trans['transaction_type']; ?>
                                </span>
                            </td>
                            <td><?php echo $trans['quantity']; ?></td>
                            <td><?php echo $trans['remarks'] ?: '-'; ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($trans['created_at'])); ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">No transactions yet</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
