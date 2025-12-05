<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Employee') {
    header("Location: ../index.php");
    exit();
}

// Get products
$products_result = mysqli_query($conn, "SELECT * FROM products ORDER BY product_name");

// Get my transactions
$transactions_query = "SELECT t.*, p.product_name 
                       FROM inventory_transactions t 
                       JOIN products p ON t.product_id = p.product_id 
                       WHERE t.user_id = {$_SESSION['user_id']} 
                       ORDER BY t.created_at DESC 
                       LIMIT 20";
$transactions_result = mysqli_query($conn, $transactions_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Inventory System</title>
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
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card h2 { color: #333; margin-bottom: 20px; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #333; }
        table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        table tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: 500; }
        .badge.stock-in { background: #d4edda; color: #155724; }
        .badge.stock-out { background: #fff3cd; color: #856404; }
        .print-btn { background: #2a5298; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; margin-bottom: 20px; }
        .print-btn:hover { background: #1e3c72; }
        @media print { .header, .sidebar, .print-btn { display: none; } .container { display: block; } .main-content { padding: 0; } }
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
            <a href="reports.php" class="active">View Reports</a>
            <a href="profile.php">My Profile</a>
        </div>
        
        <div class="main-content">
            <h2 class="page-title">Reports</h2>
            
            <button onclick="window.print()" class="print-btn">Print Reports</button>
            
            <div class="card">
                <h2>Products Inventory</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <tr>
                            <td><?php echo $product['product_id']; ?></td>
                            <td><?php echo $product['product_name']; ?></td>
                            <td><?php echo $product['description']; ?></td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td><?php echo number_format($product['price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>My Transaction History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Previous Qty</th>
                            <th>New Qty</th>
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
                            <td><?php echo $trans['previous_quantity']; ?></td>
                            <td><?php echo $trans['new_quantity']; ?></td>
                            <td><?php echo $trans['remarks'] ?: '-'; ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($trans['created_at'])); ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #666;">No transactions yet</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
