<?php
session_start();
$title = "Shop Graduation Products";
include("includes/header.php");
include("includes/db_connect.php");

// Get category filter if present
$category_filter = '';
$category_query_string = '';
if (isset($_GET['category'])) {
    $category = $_GET['category'];
    $category_filter = "WHERE category = '$category'";
    $category_query_string = "category=$category&";
}

$search_query = '';
$search_sql_filter = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql_filter = ($category_filter ? " AND " : " WHERE ") .
        "(productname LIKE '%$search_query%' OR description LIKE '%$search_query%')";
}


// Pagination settings
$items_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Get total number of products matching filter
$count_sql = "SELECT COUNT(*) as total FROM product $category_filter $search_sql_filter";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $items_per_page);

// Get products with pagination
$sql = "SELECT * FROM product $category_filter $search_sql_filter LIMIT $items_per_page OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>

<div class="page-header">
<link rel="stylesheet" href="css/product.css">
    <div class="container">
        <h1>Graduation Products</h1>
        <p>Showing Products</p>
    </div>
</div>

<div class="search-bar-wrapper text-center mb-4">
    <form action="products.php" method="get" class="search-form">
        <?php if (!empty($_GET['category'])): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category']); ?>">
        <?php endif; ?>
        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<style>
/* Add these styles for stock display */
.stock-info {
    margin: 5px 0;
    font-size: 0.9em;
    color: #666;
}
.in-stock {
    color: #28a745;
}
.low-stock {
    color: #ffc107;
}
.out-of-stock {
    color: #dc3545;
    font-weight: bold;
}
.sold-out-overlay {
    position: absolute;
    top: 20px;
    right: -30px;
    background-color: #dc3545;
    color: white;
    padding: 5px 30px;
    transform: rotate(45deg);
    font-weight: bold;
    z-index: 5;
}
.product-card {
    position: relative;
    overflow: hidden;
}
.btn-disabled {
    background-color: #ccc;
    cursor: not-allowed;
    opacity: 0.7;
}
</style>

<div class="products-section">
    <div class="container">
           
        <div class="products-grid">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="product-card">';
                    
                    // Add "Sold Out" overlay if stock is 0
                    if ($row["stock_quantity"] <= 0) {
                        echo '<div class="sold-out-overlay">SOLD OUT</div>';
                    }
                    
                    echo '<img src="' . $row["imagepath"] . '" alt="' . htmlspecialchars($row["productname"]) . '">';
                    echo '<h3>' . htmlspecialchars($row["productname"]) . '</h3>';
                    echo '<p class="price">RM' . number_format($row["price"], 2) . '</p>';
                    
                    // Add stock quantity display with color coding
                    if ($row["stock_quantity"] > 10) {
                        echo '<p class="stock-info in-stock">In Stock: ' . $row["stock_quantity"] . ' available</p>';
                    } elseif ($row["stock_quantity"] > 0) {
                        echo '<p class="stock-info low-stock">Low Stock: Only ' . $row["stock_quantity"] . ' left</p>';
                    } else {
                        echo '<p class="stock-info out-of-stock">Out of Stock</p>';
                    }
                    
                    echo '<a href="product_details.php?id=' . $row["productid"] . '" class="btn-secondary">View Details</a>';
                    
                    echo '<form class="add-to-cart-form" data-product-id="' . $row["productid"] . '">';
                    echo '<input type="hidden" name="productid" value="' . $row["productid"] . '">';
                    echo '<input type="hidden" name="quantity" value="1">';
                    
                    // Disable "Add to Cart" button if out of stock
                    if ($row["stock_quantity"] > 0) {
                        echo '<button type="submit" class="btn-primary">Add to Cart</button>';
                    } else {
                        echo '<button type="button" class="btn-primary btn-disabled" disabled>Out of Stock</button>';
                    }
                    
                    echo '</form>';
                    echo '</div>';
                    echo '<div id="cart-message" style="display: none;" class="alert"></div>';
                }
            } else {
                echo "<p class='no-products'>No products found</p>";
            }
            ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="product.php?<?php echo $category_query_string; ?>page=<?php echo ($page - 1); ?>" class="pagination-prev"><i class="fas fa-chevron-left"></i> Previous</a>
            <?php endif; ?>

            <div class="pagination-numbers">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="product.php?<?php echo $category_query_string; ?>page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>

            <?php if ($page < $total_pages): ?>
                <a href="product.php?<?php echo $category_query_string; ?>page=<?php echo ($page + 1); ?>" class="pagination-next">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$conn->close();
include("includes/footer.php"); 
?>
<script>
// Replace the existing script in products.php with this
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            
            // Create FormData object
            const formData = new FormData();
            formData.append('productid', productId);
            formData.append('quantity', 1);
            
            // Send AJAX request
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Create message element
                const messageDiv = document.createElement('div');
                
                if (data.success) {
                    messageDiv.className = 'add-to-cart-message-success';
                    messageDiv.textContent = data.message;
                } else {
                    messageDiv.className = 'add-to-cart-message-error';
                    messageDiv.textContent = data.message;
                    
                    // If login is required, redirect after showing message
                    if (data.login_required) {
                        setTimeout(() => {
                            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                        }, 2000);
                    }
                }
                
                // Insert message after the form
                this.parentNode.insertBefore(messageDiv, this.nextSibling);
                
                // Remove message after 3 seconds
                setTimeout(() => {
                    messageDiv.remove();
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error message
                const errorMessage = document.createElement('div');
                errorMessage.className = 'add-to-cart-message error';
                errorMessage.textContent = 'Failed to add product to cart.';
                
                // Insert message after the form
                this.parentNode.insertBefore(errorMessage, this.nextSibling);
                
                // Remove message after 3 seconds
                setTimeout(() => {
                    errorMessage.remove();
                }, 3000);
            });
        });
    });
});
</script>