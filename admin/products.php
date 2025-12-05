<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Add new product
if (isset($_POST['add_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    
    $query = "INSERT INTO products (product_name, description, quantity, price) 
             VALUES ('$product_name', '$description', $quantity, $price)";
    if (mysqli_query($conn, $query)) {
        $message = 'Product added successfully';
        
        $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                     VALUES ({$_SESSION['user_id']}, 'Add Product', 'Added product: $product_name')";
        mysqli_query($conn, $log_query);
    } else {
        $error = 'Failed to add product';
    }
}

// Update product
if (isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    
    $query = "UPDATE products SET product_name = '$product_name', description = '$description', 
              quantity = $quantity, price = $price WHERE product_id = $product_id";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Product updated successfully';
        
        $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                     VALUES ({$_SESSION['user_id']}, 'Update Product', 'Updated product: $product_name')";
        mysqli_query($conn, $log_query);
    } else {
        $error = 'Failed to update product';
    }
}

// Delete product
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_name FROM products WHERE product_id = $product_id"));
    
    $query = "DELETE FROM products WHERE product_id = $product_id";
    if (mysqli_query($conn, $query)) {
        $message = 'Product deleted successfully';
        
        $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                     VALUES ({$_SESSION['user_id']}, 'Delete Product', 'Deleted product: {$product['product_name']}')";
        mysqli_query($conn, $log_query);
    }
    header("Location: products.php");
    exit();
}

// Get all products
$products_result = mysqli_query($conn, "SELECT * FROM products ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Inventory System</title>
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
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #2a5298;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e3c72;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
            padding: 5px 15px;
            font-size: 12px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            font-size: 12px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
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
            <a href="products.php" class="active">Manage Products</a>
            <a href="reports.php">Reports</a>
            <a href="logs.php">Activity Logs</a>
        </div>
        
        <div class="main-content">
            <h2 class="page-title">Manage Products</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Add New Product</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" value="0" required>
                        </div>
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" value="0.00" required>
                        </div>
                    </div>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Products List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Price</th>
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
                            <td><?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <button class="btn btn-warning" onclick='openEditModal(<?php echo json_encode($product); ?>)'>
                                    Edit
                                </button>
                                <a href="?delete=<?php echo $product['product_id']; ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this product?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit Product</h3>
            <form method="POST" action="">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" id="edit_product_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="edit_quantity" required>
                    </div>
                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" step="0.01" name="price" id="edit_price" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(product) {
            document.getElementById('edit_product_id').value = product.product_id;
            document.getElementById('edit_product_name').value = product.product_name;
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_quantity').value = product.quantity;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>
</html>
