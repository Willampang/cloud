<?php
// File: admin.php
session_start();
$title = "Admin Dashboard - Graduation Shop";

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include header file (with admin navigation)
include("includes/header.php");

// Include database connection
include("includes/db_connect.php");

// Dashboard statistics
$stats = array();

// Total products
$product_query = "SELECT COUNT(*) as count FROM product";
$result = $conn->query($product_query);
$stats['products'] = $result->fetch_assoc()['count'];

// Total users
$user_query = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($user_query);
$stats['users'] = $result->fetch_assoc()['count'];

// Total orders
$order_query = "SELECT COUNT(*) as count FROM `order`";
$result = $conn->query($order_query);
$stats['orders'] = $result->fetch_assoc()['count'];

// Total revenue
$revenue_query = "SELECT SUM(amount) as total FROM `order`";
$result = $conn->query($revenue_query);
$stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Get most popular products
$popular_products_query = "SELECT p.productname, SUM(oi.quantity) as total_sold
                         FROM product p
                         JOIN order_item oi ON p.productid = oi.product_id
                         GROUP BY p.productid
                         ORDER BY total_sold DESC
                         LIMIT 5";
$popular_products_result = $conn->query($popular_products_query);

// Get recent orders
$recent_orders_query = "SELECT o.*, u.first_name, u.last_name 
                      FROM `order` o
                      JOIN users u ON o.user_id = u.id
                      ORDER BY o.updated_at DESC
                      LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_query);

// Get low stock products - without joining to non-existent category table
$low_stock_query = "SELECT p.* 
                   FROM product p
                   WHERE stock_quantity <= 10 
                   ORDER BY stock_quantity ASC 
                   LIMIT 10";
$low_stock = $conn->query($low_stock_query);
?>
<link rel="stylesheet" href="css/admin.css">

<style>
/* Additional styles for stock status */
.stock-status {
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
    text-align: center;
    min-width: 100px;
}
.status-out-of-stock {
    background-color: #dc3545;
    color: white;
}
.status-critical {
    background-color: #f8d7da;
    color: #721c24;
}
.status-low {
    background-color: #fff3cd;
    color: #856404;
}
.quick-update-form {
    display: flex;
    align-items: center;
    margin-top: 5px;
}
.quick-update-form input {
    width: 60px;
    text-align: center;
    margin-right: 5px;
}
.quick-update-form button {
    background: #007bff;
    color: white;
    border: none;
    padding: 3px 8px;
    cursor: pointer;
    border-radius: 3px;
}
.low-stock-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.low-stock-header h3 {
    margin: 0;
}
.inventory-alert {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
    padding: 10px 15px;
    margin-bottom: 20px;
    color: #721c24;
}
</style>

<!-- Dashboard content starts here -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-box"></i></div>
        <div class="stat-info">
            <h3>Total Products</h3>
            <p><?php echo $stats['products']; ?></p>
            <a href="admin_products.php" class="stat-link">Manage Products</a>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Users</h3>
            <p><?php echo $stats['users']; ?></p>
            <a href="admin_customers.php" class="stat-link">View Customers</a>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-info">
            <h3>Total Orders</h3>
            <p><?php echo $stats['orders']; ?></p>
            <a href="admin_orders.php" class="stat-link">View Orders</a>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <h3>Total Revenue</h3>
            <p>RM <?php echo number_format($stats['revenue'], 2); ?></p>
        </div>
    </div>
</div>

<div class="dashboard-tables">
    <div class="table-container">
        <h3>Popular Products</h3>
        <?php if($popular_products_result && $popular_products_result->num_rows > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Sold</th>
                </tr>
            </thead>
            <tbody>
                <?php while($product = $popular_products_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $product['productname']; ?></td>
                    <td><?php echo $product['total_sold']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No product sales data available.</p>
        <?php endif; ?>
    </div>
    
    <div class="table-container">
        <h3>Recent Orders</h3>
        <?php if($recent_orders_result && $recent_orders_result->num_rows > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = $recent_orders_result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $order['order_id']; ?></td>
                    <td><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></td>
                    <td>RM<?php echo number_format($order['amount'], 2); ?></td>
                    <td>
                        <span class="status-badge <?php echo strtolower($order['order_status']); ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($order['updated_at'])); ?></td>
                    <td>
                        <a href="admin_view_order.php?id=<?php echo $order['order_id']; ?>" class="btn-sm btn-primary">View</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No recent orders found.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Low Stock Products Section -->
<div class="dashboard-tables">
    <div class="table-container full-width">
        <div class="low-stock-header">
            <h3><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> Inventory Alert: Low Stock Products</h3>
            <a href="admin_products.php?filter=low_stock" class="btn-primary btn-sm">View All Low Stock</a>
        </div>
        
        <?php 
        // Count out of stock items
        $out_of_stock_count = 0;
        $critical_stock_count = 0;
        
        if($low_stock && $low_stock->num_rows > 0):
            $temp_result = $low_stock;
            while($item = $temp_result->fetch_assoc()) {
                if($item['stock_quantity'] <= 0) {
                    $out_of_stock_count++;
                } else if($item['stock_quantity'] <= 5) {
                    $critical_stock_count++;
                }
            }
            // Reset the result pointer
            $low_stock->data_seek(0);
        ?>
        
        <?php if($out_of_stock_count > 0 || $critical_stock_count > 0): ?>
        <div class="inventory-alert">
            <strong>Inventory Alert:</strong> 
            <?php if($out_of_stock_count > 0): ?>
                <span><?php echo $out_of_stock_count; ?> products are out of stock</span>
            <?php endif; ?>
            
            <?php if($out_of_stock_count > 0 && $critical_stock_count > 0): ?>
                and 
            <?php endif; ?>
            
            <?php if($critical_stock_count > 0): ?>
                <span><?php echo $critical_stock_count; ?> products have critically low stock (5 or fewer units)</span>
            <?php endif; ?>
            . Please update inventory levels.
        </div>
        <?php endif; ?>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price (RM)</th>
                    <th>Stock Quantity</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($product = $low_stock->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $product['productid']; ?></td>
                    <td>
                        <?php if(!empty($product['imagepath'])): ?>
                            <img src="<?php echo $product['imagepath']; ?>" alt="<?php echo htmlspecialchars($product['productname']); ?>" class="product-thumbnail" style="max-width: 50px; max-height: 50px;">
                        <?php else: ?>
                            <span class="no-image">No Image</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($product['productname']); ?></td>
                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                    <td>RM<?php echo number_format($product['price'], 2); ?></td>
                    <td>
                        <?php echo $product['stock_quantity']; ?> units
                        
                        <!-- Quick update stock form -->
                        <form method="post" action="admin_update_stock.php" class="quick-update-form">
                            <input type="hidden" name="product_id" value="<?php echo $product['productid']; ?>">
                            <input type="number" name="quantity" value="<?php echo $product['stock_quantity']; ?>" min="0">
                            <button type="submit" name="update_stock" title="Update Stock">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </form>
                    </td>
                    <td>
                        <?php
                        // Determine stock status and display appropriate label
                        if($product['stock_quantity'] <= 0) {
                            echo '<span class="stock-status status-out-of-stock">OUT OF STOCK</span>';
                        } elseif($product['stock_quantity'] <= 5) {
                            echo '<span class="stock-status status-critical">CRITICAL</span>';
                        } else {
                            echo '<span class="stock-status status-low">LOW STOCK</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="admin_edit_product.php?id=<?php echo $product['productid']; ?>" class="btn-sm btn-primary">Edit</a>
                        <a href="admin_restock.php?id=<?php echo $product['productid']; ?>" class="btn-sm btn-success">Restock</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-check-circle" style="font-size: 2em; color: #28a745;"></i>
            <p>All products are well-stocked!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$conn->close(); 
include("includes/footer.php"); 
?>

<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        const mainContent = document.querySelector('.main-content');
        const body = document.body;

        sidebar.classList.toggle('closed');
        body.classList.toggle('sidebar-closed');
    }
    
    // Add confirmation for restock actions
    document.addEventListener('DOMContentLoaded', function() {
        const restockLinks = document.querySelectorAll('a[href^="admin_restock.php"]');
        restockLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if(!confirm('Do you want to create a restock order for this product?')) {
                    e.preventDefault();
                }
            });
        });
    });
</script>