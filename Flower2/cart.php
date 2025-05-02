<?php
// File: cart.php
session_start();
$title = "Shopping Cart - Graduation Shop";
include("includes/header.php");

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Remove item from cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['productid'] == $remove_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
}

// Handle quantity increase/decrease
foreach ($_SESSION['cart'] as $key => $item) {
    $pid = $item['productid'];
    if (isset($_POST["increase_$pid"])) {
        $_SESSION['cart'][$key]['quantity']++;
        break;
    }
    if (isset($_POST["decrease_$pid"])) {
        if ($_SESSION['cart'][$key]['quantity'] > 1) {
            $_SESSION['cart'][$key]['quantity']--;
        }
        break;
    }
}

// Update cart quantities if form submitted manually
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        $qty_field = 'qty_' . $item['productid'];
        if (isset($_POST[$qty_field]) && is_numeric($_POST[$qty_field]) && $_POST[$qty_field] > 0) {
            $_SESSION['cart'][$key]['quantity'] = (int)$_POST[$qty_field];
        }
    }
}

// Calculate total
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
?>

<link rel="stylesheet" href="css/cart.css">
<div class="page-header">
    <div class="container">
        <h1>Shopping Cart</h1>
    </div>
</div>

<div class="cart-section">
    <div class="container">
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">Item added to cart successfully!</div>
        <?php endif; ?>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart fa-4x"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="products.php" class="btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <form method="post" action="cart.php">
                <div class="cart-table">
                    <table>
                        <thead>
                            <tr>
                                <th class="product-thumbnail">Product</th>
                                <th class="product-name">Name</th>
                                <th class="product-price">Price</th>
                                <th class="product-quantity">Quantity</th>
                                <th class="product-subtotal">Subtotal</th>
                                <th class="product-remove">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $item): ?>
                                <tr>
                                    <td class="product-thumbnail">
                                        <a href="product_details.php?id=<?php echo $item['productid']; ?>">
                                            <img src="<?php echo $item['imagepath']; ?>" alt="<?php echo $item['productname']; ?>">
                                        </a>
                                    </td>
                                    <td class="product-name">
                                        <a href="product_details.php?id=<?php echo $item['productid']; ?>">
                                            <?php echo $item['productname']; ?>
                                        </a>
                                    </td>
                                    <td class="product-price">
                                        RM<?php echo number_format($item['price'], 2); ?>
                                    </td>
                                    <td class="product-quantity">
                                        <div class="quantity-control">
                                            <button type="submit" name="decrease_<?php echo $item['productid']; ?>" class="quantity-btn">−</button>
                                            <input type="number" name="qty_<?php echo $item['productid']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="10" readonly>
                                            <button type="submit" name="increase_<?php echo $item['productid']; ?>" class="quantity-btn">+</button>
                                        </div>
                                    </td>
                                    <td class="product-subtotal">
                                        RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </td>
                                    <td class="product-remove">
                                        <a href="cart.php?remove=<?php echo $item['productid']; ?>" class="remove-btn">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="cart-actions">
                    <div class="cart-update">
                        <a href="products.php" class="btn-secondary">Continue Shopping</a>
                    </div>
                </div>
            </form>

            <div class="cart-totals">
                <h2>Cart Totals</h2>
                <div class="totals-table">
                    <div class="totals-row">
                        <div class="totals-label">Subtotal</div>
                        <div class="totals-value">RM<?php echo number_format($cart_total, 2); ?></div>
                    </div>
                    <div class="totals-row">
                        <div class="totals-label">Shipping</div>
                        <div class="totals-value">
                            <?php
                            $shipping = ($cart_total >= 50) ? 0 : 5.99;
                            echo ($shipping > 0) ? 'RM' . number_format($shipping, 2) : 'Free';
                            ?>
                        </div>
                    </div>
                    <div class="totals-row total">
                        <div class="totals-label">Total</div>
                        <div class="totals-value">RM<?php echo number_format($cart_total + $shipping, 2); ?></div>
                    </div>
                </div>

                <div class="checkout-button">
                    <a href="checkout.php" class="btn-primary btn-large">Proceed to Checkout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include("includes/footer.php"); ?>
