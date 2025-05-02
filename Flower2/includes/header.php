<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("includes/db_connect.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Graduation Shop'; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
<?php 
if(isset($_SESSION['admin_id'])): ?>
    <link rel="stylesheet" href="css/admin.css">
    <?php endif; ?>
</head>
<body>
    <header>
        <?php if(isset($_SESSION['admin_id'])): ?>
<div class="admin-navbar">
    <div class="admin-logo">
        <h2>Admin Panel</h2>
    </div>
    <nav class="admin-nav">
        <ul>
        <li<?php echo (basename($_SERVER['PHP_SELF']) == 'admin.php') ? ' class="active"' : ''; ?>><a href="admin.php"><i class="fas fa-box"></i> Dashboard</a></li>
        <li<?php echo (basename($_SERVER['PHP_SELF']) == 'admin_products.php') ? ' class="active"' : ''; ?>><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
            <li<?php echo (basename($_SERVER['PHP_SELF']) == 'admin_orders.php') ? ' class="active"' : ''; ?>><a href="admin_orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
            <li<?php echo (basename($_SERVER['PHP_SELF']) == 'admin_customers.php') ? ' class="active"' : ''; ?>><a href="admin_customers.php"><i class="fas fa-users"></i> Customers</a></li>
            <li<?php echo (basename($_SERVER['PHP_SELF']) == 'admin_chat.php') ? ' class="active"' : ''; ?>><a href="admin_chat.php"><i class="fas fa-comments"></i> Chat Support
                <?php 
                // Check for unread messages badge - Only execute if $conn is available
                if(isset($conn)) {
                    $unread_query = "SELECT COUNT(*) as count FROM chat_messages cm JOIN chat_sessions cs ON cm.session_id = cs.session_id WHERE cs.status = 'active' AND cm.sender_type = 'user' AND cm.is_read = 0";
                    $unread_result = $conn->query($unread_query);
                    $unread_count = $unread_result->fetch_assoc()['count'];
                    if($unread_count > 0): ?>
                        <span class="badge badge-danger"><?php echo $unread_count; ?></span>
                    <?php endif;
                }
                ?>
            </a></li>
            <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <div class="admin-user">
        <span>Welcome, Admin</span>
        <a href="admin_logout.php" class="btn-outline-small">Logout</a>
    </div>
</div>
        <?php else: ?>
        <!-- Regular User Header -->
        <div class="top-bar">
            <div class="container">
                <p><i class="fas fa-truck"></i> Free shipping on orders over RM50</p>
                <div class="top-bar-links">
                <ul>
                <?php if(!empty($_SESSION['first_name'])): ?>
                    <li><a href="myAccount.php">My Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
                </ul>
            </div>
            <button class="nav-toggle mobile-only">
            <i class="fas fa-bars"></i>
            </button>
            <div class="mobile-overlay"></div>

            <!-- At the bottom of your page, before closing body tag -->
            <nav class="mobile-bottom-nav">
            <a href="index.php"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="products.php"><i class="fas fa-shopping-bag"></i><span>Shop</span></a>
            <a href="cart.php"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
            <a href="myAccount.php"><i class="fas fa-user"></i><span>Account</span></a>
            </nav>
            </div>
        </div>
        <nav class="main-nav">
            <div class="container">
                <a href="index.php" class="logo">Graduation<span>Shop</span></a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">All Products</a></li>
                    <li><a href="products.php?category=flowers">Flowers</a></li>
                    <li><a href="products.php?category=balloons">Balloons</a></li>
                    <li><a href="products.php?category=bears">Teddy Bears</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
                <div class="nav-icons">
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php
                        if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                            echo '<span class="cart-count">' . count($_SESSION['cart']) . '</span>';
                        }
                        ?>
                    </a>
                </div>
            </div>
        </nav>
        <?php endif; ?>
    </header>
    <main>
    <?php if(!isset($_SESSION['admin_id'])): ?>
    <!-- This empty div helps maintain the layout for regular users -->
    <div class="main-content">
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');
  const mobileOverlay = document.querySelector('.mobile-overlay');
  
  if (navToggle) {
    navToggle.addEventListener('click', function() {
      navLinks.classList.toggle('active');
      mobileOverlay.classList.toggle('active');
    });
  }
  
  if (mobileOverlay) {
    mobileOverlay.addEventListener('click', function() {
      navLinks.classList.remove('active');
      this.classList.remove('active');
    });
  }
});
    </script>