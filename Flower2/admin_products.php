<?php
// File: admin_products.php
session_start();
$title = "Manage Products - Admin Panel";
include("includes/header.php");

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Include database connection
include("includes/db_connect.php");

// Handle form submissions
$message = '';
$messageType = '';

// Delete product
if(isset($_POST['delete_product'])) {
    $product_id = $conn->real_escape_string($_POST['product_id']);
    $delete_sql = "DELETE FROM product WHERE productid = $product_id";
    
    if($conn->query($delete_sql) === TRUE) {
        $message = "Product deleted successfully";
        $messageType = "success";
    } else {
        $message = "Error deleting product: " . $conn->error;
        $messageType = "error";
    }
}

// Update product quantity
if(isset($_POST['update_quantity'])) {
    $product_id = $conn->real_escape_string($_POST['product_id']);
    $quantity = $conn->real_escape_string($_POST['quantity']);
    
    $update_sql = "UPDATE product SET stock_quantity = $quantity WHERE productid = $product_id";
    
    if($conn->query($update_sql) === TRUE) {
        $message = "Product quantity updated successfully";
        $messageType = "success";
    } else {
        $message = "Error updating product quantity: " . $conn->error;
        $messageType = "error";
    }
}

// Get products
$sql = "SELECT * FROM product ORDER BY productid DESC";
$result = $conn->query($sql);
?>

<link rel="stylesheet" href="css/admin.css">

<style>
/* Add these styles for stock level indicators */
.stock-level {
    font-weight: bold;
    padding: 3px 8px;
    border-radius: 3px;
    display: inline-block;
}
.stock-high {
    background-color: #d4edda;
    color: #155724;
}
.stock-medium {
    background-color: #fff3cd;
    color: #856404;
}
.stock-low {
    background-color: #f8d7da;
    color: #721c24;
}
.stock-zero {
    background-color: #dc3545;
    color: white;
}
.quick-edit-quantity {
    display: flex;
    align-items: center;
    margin-top: 5px;
}
.quick-edit-quantity input {
    width: 60px;
    text-align: center;
    margin: 0 5px;
}
.quick-edit-quantity button {
    background: #007bff;
    color: white;
    border: none;
    padding: 2px 5px;
    cursor: pointer;
    border-radius: 3px;
}
.product-info-column {
    min-width: 180px;
}
</style>

<div class="admin-container">
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>Manage Products</h1>
            <div class="admin-actions">
                <a href="admin_add_product.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
            </div>
        </div>
        
        <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="search-filter">
            <input type="text" id="productSearch" placeholder="Search products..." class="search-input">
            <select id="categoryFilter" class="select-input">
                <option value="">All Categories</option>
                <option value="Balloons">Balloons</option>
                <option value="Bears">Bears</option>
                <option value="Flowers">Flowers</option>
            </select>
            <select id="stockFilter" class="select-input">
                <option value="">All Stock Levels</option>
                <option value="out">Out of Stock</option>
                <option value="low">Low Stock (1-10)</option>
                <option value="available">In Stock (>10)</option>
            </select>
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (RM)</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($product = $result->fetch_assoc()): ?>
                        <tr data-stock="<?php 
                            if($product['stock_quantity'] <= 0) echo 'out';
                            else if($product['stock_quantity'] <= 10) echo 'low';
                            else echo 'available';
                        ?>" data-category="<?php echo $product['category']; ?>">
                            <td><?php echo $product['productid']; ?></td>
                            <td>
                                <?php if(!empty($product['imagepath'])): ?>
                                    <img src="<?php echo $product['imagepath']; ?>" alt="<?php echo $product['productname']; ?>" class="product-thumbnail">
                                <?php else: ?>
                                    <span class="no-image">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td class="product-info-column"><?php echo $product['productname']; ?></td>
                            <td><?php echo $product['category']; ?></td>
                            <td><?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <?php
                                // Display stock quantity with color-coding
                                $stock_class = '';
                                $stock_label = '';
                                
                                if($product['stock_quantity'] <= 0) {
                                    $stock_class = 'stock-zero';
                                    $stock_label = 'OUT OF STOCK';
                                } 
                                else if($product['stock_quantity'] <= 5) {
                                    $stock_class = 'stock-low';
                                    $stock_label = 'VERY LOW';
                                }
                                else if($product['stock_quantity'] <= 10) {
                                    $stock_class = 'stock-medium';
                                    $stock_label = 'LOW';
                                }
                                else {
                                    $stock_class = 'stock-high';
                                    $stock_label = 'IN STOCK';
                                }
                                ?>
                                <span class="stock-level <?php echo $stock_class; ?>"><?php echo $stock_label; ?></span>
                                <div><?php echo $product['stock_quantity']; ?> units</div>
                                
                                <!-- Quick edit quantity form -->
                                <form method="post" action="" class="quick-edit-quantity">
                                    <input type="hidden" name="product_id" value="<?php echo $product['productid']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $product['stock_quantity']; ?>" min="0">
                                    <button type="submit" name="update_quantity" title="Update Stock Quantity">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="actions">
                                <a href="admin_edit_product.php?id=<?php echo $product['productid']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                <form method="post" action="" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $product['productid']; ?>">
                                    <button type="submit" name="delete_product" class="btn-delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="no-results">No products found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// JavaScript for filtering products
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('productSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const stockFilter = document.getElementById('stockFilter');
    const productRows = document.querySelectorAll('tbody tr');
    
    // Function to filter products
    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryValue = categoryFilter.value;
        const stockValue = stockFilter.value;
        
        productRows.forEach(row => {
            const productName = row.querySelector('.product-info-column').textContent.toLowerCase();
            const productCategory = row.getAttribute('data-category');
            const stockStatus = row.getAttribute('data-stock');
            
            // Check if product matches all filters
            const matchesSearch = productName.includes(searchTerm);
            const matchesCategory = !categoryValue || productCategory === categoryValue;
            const matchesStock = !stockValue || stockStatus === stockValue;
            
            if (matchesSearch && matchesCategory && matchesStock) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Add event listeners to filters
    searchInput.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);
    stockFilter.addEventListener('change', filterProducts);
    
    // Initialize tooltips
    if (typeof $(document).tooltip === 'function') {
        $(document).tooltip();
    }
});
</script>

<?php
$conn->close();
include("includes/footer.php");
?>