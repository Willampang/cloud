<?php
session_start();
$title = "Graduation Shop - Celebrate Your Achievement";
include("includes/header.php");
?>

<div class="hero-section" style="background-image: url('image/graduationbg.jpg');">
    <div class="hero-content">
        <h1>Celebrate Your Big Day</h1>
        <p>Find everything you need to make your graduation special</p>
        <a href="products.php" class="btn-primary">Shop Now</a>
    </div>
</div>

<section class="featured-categories">
    <h2>Shop By Category</h2>
    <div class="categories-container">
        <div class="category-card">
            <img src="image/flower1.jpeg" alt="Graduation Flowers">
            <h3>Flowers</h3>
            <a href="products.php?category=flowers" class="btn-secondary">View Collection</a>
        </div>
        <div class="category-card">
            <img src="image/ballon1.jpeg" alt="Graduation Balloons">
            <h3>Balloons</h3>
            <a href="products.php?category=balloons" class="btn-secondary">View Collection</a>
        </div>
        <div class="category-card">
            <img src="image/bear1.jpeg" alt="Graduation Teddy Bears">
            <h3>Teddy Bears</h3>
            <a href="products.php?category=bears" class="btn-secondary">View Collection</a>
        </div>
    </div>
</section>

<section class="featured-products">
    <h2>Featured Products</h2>
    <div class="products-container">
        <?php
        // Database connection
        include("includes/db_connect.php");
        
        // Fetch featured products (limit to 4)
        $sql = "SELECT * FROM product ORDER BY RAND() LIMIT 4";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<div class="product-card">';
                echo '<img src="' . $row["imagepath"] . '" alt="' . $row["productname"] . '">';
                echo '<h4>' . $row["productname"] . '</h4>';
                echo '<p class="price">RM' . number_format($row["price"], 2) . '</p>';
                echo '<a href="product_details.php?id=' . $row["productid"] . '" class="btn-secondary">View Details</a>';
                echo '<form class="add-to-cart-form" data-product-id="' . $row["productid"] . '">';
                echo '<input type="hidden" name="productid" value="' . $row["productid"] . '">';
                echo '<input type="hidden" name="quantity" value="1">';
                echo '<button type="submit" class="btn-primary">Add to Cart</button>';
                echo '</form>';
                echo '<div class="cart-message" style="display: none;"></div>';
                echo '</div>';
            }
        } else {
            echo "<p>No products found</p>";
        }
        $conn->close();
        ?>
    </div>
    <div class="view-all">
        <a href="products.php" class="btn-primary">View All Products</a>
    </div>
</section>
<section class="graduation-message">
    <div class="message-content">
        <h2>Make Your Graduation Unforgettable</h2>
        <p>Graduation is a milestone achievement that deserves to be celebrated in style. Our carefully curated selection of graduation gifts, flowers, balloons, and teddy bears are perfect for commemorating this special occasion.</p>
        <a href="products.php" class="btn-primary">Start Shopping</a>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality for all add-to-cart-form elements
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            
            // Create FormData object
            const formData = new FormData();
            formData.append('productid', this.querySelector('input[name="productid"]').value);
            formData.append('quantity', this.querySelector('input[name="quantity"]').value);
            
            // Find the message div for this specific product
            const messageDiv = this.nextElementSibling;
            
            // Send AJAX request
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'cart-message success';
                    messageDiv.textContent = data.message;
                } else {
                    messageDiv.className = 'cart-message error';
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
                messageDiv.className = 'cart-message error';
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.style.display = 'block';
                
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            });
        });
    });
});
</script>
<?php include("includes/footer.php"); ?>