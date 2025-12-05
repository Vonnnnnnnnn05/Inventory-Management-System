<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Employee') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Handle Stock-In
if (isset($_POST['stock_in'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    if ($quantity > 0) {
        // Get current quantity
        $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE product_id = $product_id"));
        $previous_qty = $product['quantity'];
        $new_qty = $previous_qty + $quantity;
        
        // Update product quantity
        $update_query = "UPDATE products SET quantity = $new_qty WHERE product_id = $product_id";
        
        if (mysqli_query($conn, $update_query)) {
            // Log transaction
            $trans_query = "INSERT INTO inventory_transactions (product_id, user_id, transaction_type, quantity, previous_quantity, new_quantity, remarks) 
                           VALUES ($product_id, {$_SESSION['user_id']}, 'Stock In', $quantity, $previous_qty, $new_qty, '$remarks')";
            mysqli_query($conn, $trans_query);
            
            // Log activity
            $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                         VALUES ({$_SESSION['user_id']}, 'Stock In', 'Added $quantity units of {$product['product_name']}')";
            mysqli_query($conn, $log_query);
            
            $message = 'Stock-in completed successfully';
        } else {
            $error = 'Failed to update stock';
        }
    } else {
        $error = 'Quantity must be greater than 0';
    }
}

// Handle Stock-Out
if (isset($_POST['stock_out'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    if ($quantity > 0) {
        // Get current quantity
        $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE product_id = $product_id"));
        $previous_qty = $product['quantity'];
        
        if ($previous_qty >= $quantity) {
            $new_qty = $previous_qty - $quantity;
            
            // Update product quantity
            $update_query = "UPDATE products SET quantity = $new_qty WHERE product_id = $product_id";
            
            if (mysqli_query($conn, $update_query)) {
                // Log transaction
                $trans_query = "INSERT INTO inventory_transactions (product_id, user_id, transaction_type, quantity, previous_quantity, new_quantity, remarks) 
                               VALUES ($product_id, {$_SESSION['user_id']}, 'Stock Out', $quantity, $previous_qty, $new_qty, '$remarks')";
                mysqli_query($conn, $trans_query);
                
                // Log activity
                $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                             VALUES ({$_SESSION['user_id']}, 'Stock Out', 'Removed $quantity units of {$product['product_name']}')";
                mysqli_query($conn, $log_query);
                
                $message = 'Stock-out completed successfully';
            } else {
                $error = 'Failed to update stock';
            }
        } else {
            $error = 'Insufficient stock available. Current stock: ' . $previous_qty;
        }
    } else {
        $error = 'Quantity must be greater than 0';
    }
}

// Get all products
$products_result = mysqli_query($conn, "SELECT * FROM products ORDER BY product_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - Inventory System</title>
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
        .message { padding: 12px; margin-bottom: 20px; border-radius: 5px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card h2 { color: #333; margin-bottom: 20px; font-size: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #333; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #333; }
        table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        table tr:hover { background: #f8f9fa; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal.active { display: flex; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; }
        .modal-content h3 { margin-bottom: 20px; color: #333; }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
        .current-stock { font-size: 18px; font-weight: bold; color: #2a5298; margin: 10px 0; }
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
            <a href="stock.php" class="active">Stock Management</a>
            <a href="reports.php">View Reports</a>
            <a href="profile.php">My Profile</a>
        </div>
        
        <div class="main-content">
            <h2 class="page-title">Stock Management</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Products List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Current Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <tr>
                            <td><?php echo $product['product_id']; ?></td>
                            <td><?php echo $product['product_name']; ?></td>
                            <td><?php echo $product['description']; ?></td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td>
                                <button class="btn btn-success" style="padding: 5px 15px; font-size: 12px;" 
                                        onclick='openStockInModal(<?php echo json_encode($product); ?>)'>
                                    Stock In
                                </button>
                                <button class="btn btn-warning" style="padding: 5px 15px; font-size: 12px;" 
                                        onclick='openStockOutModal(<?php echo json_encode($product); ?>)'>
                                    Stock Out
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="stockInModal" class="modal">
        <div class="modal-content">
            <h3>Stock In</h3>
            <form method="POST" action="">
                <input type="hidden" name="product_id" id="stockin_product_id">
                <div class="form-group">
                    <label>Product</label>
                    <input type="text" id="stockin_product_name" readonly>
                </div>
                <div class="current-stock">
                    Current Stock: <span id="stockin_current_stock"></span>
                </div>
                <div class="form-group">
                    <label>Quantity to Add</label>
                    <input type="number" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" placeholder="Optional notes about this transaction"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="stock_in" class="btn btn-success">Add Stock</button>
                    <button type="button" class="btn btn-secondary" onclick="closeStockInModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="stockOutModal" class="modal">
        <div class="modal-content">
            <h3>Stock Out</h3>
            <form method="POST" action="">
                <input type="hidden" name="product_id" id="stockout_product_id">
                <div class="form-group">
                    <label>Product</label>
                    <input type="text" id="stockout_product_name" readonly>
                </div>
                <div class="current-stock">
                    Current Stock: <span id="stockout_current_stock"></span>
                </div>
                <div class="form-group">
                    <label>Quantity to Remove</label>
                    <input type="number" name="quantity" min="1" id="stockout_quantity" required>
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" placeholder="Optional notes about this transaction"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="stock_out" class="btn btn-warning">Remove Stock</button>
                    <button type="button" class="btn btn-secondary" onclick="closeStockOutModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openStockInModal(product) {
            document.getElementById('stockin_product_id').value = product.product_id;
            document.getElementById('stockin_product_name').value = product.product_name;
            document.getElementById('stockin_current_stock').textContent = product.quantity;
            document.getElementById('stockInModal').classList.add('active');
        }
        
        function closeStockInModal() {
            document.getElementById('stockInModal').classList.remove('active');
        }
        
        function openStockOutModal(product) {
            document.getElementById('stockout_product_id').value = product.product_id;
            document.getElementById('stockout_product_name').value = product.product_name;
            document.getElementById('stockout_current_stock').textContent = product.quantity;
            document.getElementById('stockout_quantity').max = product.quantity;
            document.getElementById('stockOutModal').classList.add('active');
        }
        
        function closeStockOutModal() {
            document.getElementById('stockOutModal').classList.remove('active');
        }
    </script>
</body>
</html>
