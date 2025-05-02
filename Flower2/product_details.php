<?php
session_start();
include("includes/db_connect.php");

// Check if product ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = $_GET['id'];

// Fetch product details
$sql = "SELECT * FROM product WHERE productid = $product_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();
$title = $product['productname'] . " - Graduation Shop";
include("includes/header.php");
?>

<style>
/* Add these styles for stock display */
.stock-status {
    margin: 10px 0;
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: bold;
    display: inline-block;
}
.in-stock {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.low-stock {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
.out-of-stock {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.btn-disabled {
    background-color: #ccc;
    cursor: not-allowed;
    opacity: 0.7;
}
</style>

<div class="product-details-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> &gt; 
            <a href="products.php">Products</a> &gt; 
            <span><?php echo $product['productname']; ?></span>
        </div>
        
        <div class="product-details">
            <div class="product-image">
                <img src="<?php echo $product['imagepath']; ?>" alt="<?php echo $product['productname']; ?>">
            </div>
            
            <div class="product-info">
                <h1><?php echo $product['productname']; ?></h1>
                <div class="product-price">RM<?php echo number_format($product['price'], 2); ?></div>
                
                <!-- Stock status display -->
                <?php if($product['stock_quantity'] > 10): ?>
                    <div class="stock-status in-stock">
                        <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)
                    </div>
                <?php elseif($product['stock_quantity'] > 0): ?>
                    <div class="stock-status low-stock">
                        <i class="fas fa-exclamation-circle"></i> Low Stock (Only <?php echo $product['stock_quantity']; ?> left)
                    </div>
                <?php else: ?>
                    <div class="stock-status out-of-stock">
                        <i class="fas fa-times-circle"></i> Out of Stock
                    </div>
                <?php endif; ?>
                
                <div class="product-description">
                    <?php
                    // In a real application, you'd have a description field in your database
                    echo "<p>Celebrate your graduation with this beautiful " . $product['productname'] . ". Perfect for commemorating your academic achievement and making your special day even more memorable.</p>";
                    ?>
                </div>

                <!-- Show add to cart form only if in stock -->
                <?php if($product['stock_quantity'] > 0): ?>
                    <form id="add-to-cart-form" class="add-to-cart-form">
                        <input type="hidden" name="productid" value="<?php echo $product['productid']; ?>">
                        
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" onclick="decrementQuantity()">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                <button type="button" class="quantity-btn" onclick="incrementQuantity()">+</button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary btn-large">Add to Cart</button>
                    </form>
                <?php else: ?>
                    <div class="out-of-stock-message">
                        <p>Sorry, this product is currently out of stock.</p>
                        <p>Please check back later or browse our other products.</p>
                        <a href="products.php" class="btn-secondary">Browse Other Products</a>
                    </div>
                <?php endif; ?>

                <div id="cart-message" style="display: none;" class="alert"></div>
                
                <div class="product-meta">
                    <p><strong>Product ID:</strong> <?php echo $product['productid']; ?></p>
                    <p><strong>Categories:</strong> Graduation, Gifts</p>
                </div>
            </div>
        </div>
        
        <div class="product-tabs">
            <div class="tabs-nav">
                <button class="tab-btn active" data-tab="description">Description</button>
                <button class="tab-btn" data-tab="shipping">Shipping Info</button>
                <button class="tab-btn" data-tab="returns">Returns & Refunds</button>
            </div>
            
            <div class="tab-content" id="description" style="display: block;">
                <h3>Product Description</h3>
                <p>This premium graduation gift is designed specifically for those special moments when academic achievement deserves recognition. Our <?php echo $product['productname']; ?> is crafted with high-quality materials to ensure it becomes a cherished memento of your educational journey.</p>
                <p>Whether you're celebrating your own graduation or congratulating someone special, this gift is perfect for the occasion. It comes beautifully packaged and ready to present to the graduate.</p>
                <p>Features:</p>
                <ul>
                    <li>High-quality materials</li>
                    <li>Beautiful graduation-themed design</li>
                    <li>Perfect size for display or gifting</li>
                    <li>Commemorates academic achievement</li>
                </ul>
            </div>
            
            <div class="tab-content" id="shipping" style="display: none;">
                <h3>Shipping Information</h3>
                <p>We offer the following shipping options:</p>
                <ul>
                    <li><strong>Standard Shipping:</strong> 3-5 business days (RM5.99)</li>
                    <li><strong>Express Shipping:</strong> 1-2 business days (RM12.99)</li>
                </ul>
                <p>Free shipping on all orders over RM50!</p>
                <p>Please note that delivery times may vary depending on your location. Once your order ships, you will receive a tracking number via email.</p>
            </div>
            
            <div class="tab-content" id="returns" style="display: none;">
                <h3>Returns & Refunds</h3>
                <p>We want you to be completely satisfied with your purchase. If for any reason you're not happy with your order, you can return it within 30 days of receipt for a full refund or exchange.</p>
                <p>To be eligible for a return, the item must be unused and in the same condition that you received it, with the original packaging.</p>
                <p>For more information about our return policy, please contact our customer service team.</p>
            </div>
        </div>
    </div>
</div>

<script>
    function incrementQuantity() {
        const input = document.getElementById('quantity');
        const currentValue = parseInt(input.value);
        const maxValue = parseInt(input.getAttribute('max'));
        if (currentValue < maxValue) {
            input.value = currentValue + 1;
        }
    }
    
    function decrementQuantity() {
        const input = document.getElementById('quantity');
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
        }
    }
    
    // Tab functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all buttons
            tabBtns.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab and mark button as active
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).style.display = 'block';
            this.classList.add('active');
        });
    });

<?php if($product['stock_quantity'] > 0): ?>
// Add to cart AJAX functionality
document.getElementById('add-to-cart-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('cart-message');
        
        if (data.success) {
            messageDiv.className = 'alert alert-success';
            messageDiv.textContent = data.message;
        } else {
            messageDiv.className = 'alert alert-error';
            messageDiv.textContent = data.message;
            
            // If login is required, redirect after showing message
            if (data.login_required) {
                setTimeout(() => {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                }, 2000);
            }
        }
        
        // Show the message
        messageDiv.style.display = 'block';
        
        // Hide message after 3 seconds
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 3000);
    })
    .catch(error => {
        console.error('Error:', error);
        const messageDiv = document.getElementById('cart-message');
        messageDiv.className = 'alert alert-error';
        messageDiv.textContent = 'An error occurred. Please try again.';
        messageDiv.style.display = 'block';
        
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 3000);
    });
});
<?php endif; ?>

</script>

<?php 
$conn->close();
include("includes/footer.php"); 
?>