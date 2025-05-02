<?php
session_start();
include("includes/db_connect.php");

// Check if user is logged in
if (!isset($_SESSION['first_name']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add products to cart', 'login_required' => true]);
    exit;
}

// Rest of your existing add_to_cart.php code...
if (isset($_POST['productid']) && isset($_POST['quantity'])) {
    $product_id = (int)$_POST['productid'];
    $quantity = (int)$_POST['quantity'];
    
    // Validate quantity (must be positive)
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
        exit;
    }
    
    // Get product information from database
    $sql = "SELECT * FROM product WHERE productid = $product_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Check if product is already in cart
        $product_in_cart = false;
        
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['productid'] == $product_id) {
                    // Update quantity
                    $_SESSION['cart'][$key]['quantity'] += $quantity;
                    $product_in_cart = true;
                    break;
                }
            }
        } else {
            // Initialize cart if not exists
            $_SESSION['cart'] = array();
        }
        
        // Add new product to cart if not already added
        if (!$product_in_cart) {
            $_SESSION['cart'][] = array(
                'productid' => $product_id,
                'productname' => $product['productname'],
                'price' => $product['price'],
                'imagepath' => $product['imagepath'],
                'quantity' => $quantity
            );
        }
        
        echo json_encode(['success' => true, 'message' => 'Product added to cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing product information']);
}

$conn->close();
?>