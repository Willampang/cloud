<?php
session_start();
$title = "Checkout - Graduation Shop";
include("includes/header.php");
include("includes/db_connect.php");

// Initialize variables
$logged_in = isset($_SESSION['user_id']);
$order_success = false;
$payment_error = false;
$checkout_error = '';

// Retrieve cart information
if(!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$cart_total = 0;
foreach($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
$shipping = ($cart_total >= 50) ? 0 : 5.99;
$total = $cart_total + $shipping;

// User data retrieval - updated to check for first_name
$user_data = array();
if(isset($_SESSION['user_id']) || isset($_SESSION['user_email']) || isset($_SESSION['first_name'])) {
    $logged_in = true;
    
    // Try to get user data by ID first
    if(isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $user_sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($user_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    // If no results by ID or ID not set, check by email
    if(empty($user_data) && isset($_SESSION['user_email'])) {
        $user_email = $_SESSION['user_email'];
        $email_sql = "SELECT * FROM users WHERE usergmail = ?";
        $email_stmt = $conn->prepare($email_sql);
        $email_stmt->bind_param("s", $user_email);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        
        if($email_result->num_rows > 0) {
            $user_data = $email_result->fetch_assoc();
            // Update the session with the correct user ID
            $_SESSION['user_id'] = $user_data['id'];
        }
        $email_stmt->close();
    }
    
    // If still no results, check by first_name
    if(empty($user_data) && isset($_SESSION['first_name'])) {
        $first_name = $_SESSION['first_name'];
        $name_sql = "SELECT * FROM users WHERE first_name = ?";
        $name_stmt = $conn->prepare($name_sql);
        $name_stmt->bind_param("s", $first_name);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();
        
        if($name_result->num_rows > 0) {
            $user_data = $name_result->fetch_assoc();
            // Update the session with the correct user ID and email
            $_SESSION['user_id'] = $user_data['id'];
            if(!isset($_SESSION['user_email'])) {
                $_SESSION['user_email'] = $user_data['usergmail'];
            }
        }
        $name_stmt->close();
    }
    
    // Close the database connection
    $conn->close();
} else {
    $logged_in = false;
}
// Process form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    include("includes/db_connect.php");

    $payment_method = $_POST['payment_method'];
    $payment_valid = false;

    if($payment_method == 'credit_card') {
        $card_number = preg_replace('/\s+/', '', $_POST['card_number']);
        $card_expiry = $_POST['expiry'];
        $card_cvv = $_POST['cvv'];

        $payment_valid = ( 
            strlen($card_number) == 16 && 
            is_numeric($card_number) && 
            preg_match('/^\d{2}\/\d{2}$/', $card_expiry) && 
            is_numeric($card_cvv) && 
            strlen($card_cvv) >= 3 
        );
        
        if (!$payment_valid) {
            $checkout_error = "Invalid credit card information. Please check your card details.";
            $payment_error = true;
        }
    } elseif($payment_method == 'bank_transfer' || $payment_method == 'paypal') {
        $payment_valid = true;
    } else {
        $checkout_error = "Invalid payment method selected.";
        $payment_error = true;
    }
    
    if($payment_valid) {
        $user_id = null;
        
        if($logged_in) {
            $user_id = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("UPDATE users SET address = ?, city = ?, state = ?, zip = ?, country = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $_POST['address'], $_POST['city'], $_POST['state'], $_POST['postal_code'], $_POST['country'], $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $email = $_POST['email'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $address = $_POST['address'];
            $city = $_POST['city'];
            $state = $_POST['state'];
            $postal_code = $_POST['postal_code'];
            $country = $_POST['country'];
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';

            $check_sql = "SELECT id FROM users WHERE usergmail = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if($check_result->num_rows > 0) {
                $user_row = $check_result->fetch_assoc();
                $user_id = $user_row['id'];

                $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, address = ?, city = ?, state = ?, zip = ?, country = ?, phone = ? WHERE id = ?");
                $update_stmt->bind_param("ssssssssi", $first_name, $last_name, $address, $city, $state, $postal_code, $country, $phone, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                $temp_password = password_hash(uniqid(), PASSWORD_DEFAULT);
                
                $insert_sql = "INSERT INTO users (first_name, last_name, usergmail, password, address, city, state, zip, country, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ssssssssss", $first_name, $last_name, $email, $temp_password, $address, $city, $state, $postal_code, $country, $phone);
                $insert_stmt->execute();
                $user_id = $conn->insert_id;
                $insert_stmt->close();
            }
            
            $check_stmt->close();
        }
        $shipping_address = $_POST['address'] . ', ' . $_POST['city'] . ', ' . $_POST['state'] . ' ' . $_POST['postal_code'] . ', ' . $_POST['country'];

        $stmt = $conn->prepare("INSERT INTO `order` (user_id, amount, shipping_address, order_status, updated_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("ids", $user_id, $total, $shipping_address);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();
        
        if($order_id) {
            foreach($_SESSION['cart'] as $item) {
                $stmt = $conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $item['productid'], $item['quantity'], $item['price']);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE product SET stock_quantity = stock_quantity - ? WHERE productid = ?");
                $stmt->bind_param("ii", $item['quantity'], $item['productid']);
                $stmt->execute();
                $stmt->close();
            }
        
            $order_success = true;

            $_SESSION['last_order_id'] = $order_id;
            $_SESSION['payment_method'] = $payment_method;
            $_SESSION['order_total'] = $total;
            $_SESSION['order_success_message'] = "Your payment has been processed successfully! Your order #" . $order_id . " has been placed.";

            $_SESSION['cart'] = array();

            header("Location: order_confirmation.php");
            exit();
        } else {
            $checkout_error = "Failed to create order. Please try again.";
            $payment_error = true;
        }
    }
    
    $conn->close();
}
?>
<link rel="stylesheet" href="css/checkout.css">
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <h1 class="mb-3">Checkout</h1>
            <div class="checkout-steps">
                <div class="step active">
                    <span class="step-number">1</span>
                    <p>Shopping Cart</p>
                </div>
                <div class="step active">
                    <span class="step-number">2</span>
                    <p>Checkout</p>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <p>Confirmation</p>
                </div>
            </div>
        </div>
        
        <?php if($payment_error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $checkout_error; ?>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-7 col-md-6">
                <form method="post" action="checkout.php" id="checkout-form">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4><i class="fas fa-user me-2"></i> Your Information</h4>
                        </div>
                        <div class="card-body">
                            <?php if(!$logged_in): ?>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <small class="text-muted">We'll send your order confirmation to this email</small>
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($user_data['usergmail']) ? htmlspecialchars($user_data['usergmail']) : ''; ?>" readonly>
                                <small class="text-muted">Email address cannot be changed.</small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                        value="<?php echo isset($user_data['first_name']) ? htmlspecialchars($user_data['first_name']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                        value="<?php echo isset($user_data['last_name']) ? htmlspecialchars($user_data['last_name']) : ''; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Street Address *</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo isset($user_data['address']) ? htmlspecialchars($user_data['address']) : ''; ?>" required>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="<?php echo isset($user_data['city']) ? htmlspecialchars($user_data['city']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="state" class="form-label">State/Province *</label>
                                    <input type="text" class="form-control" id="state" name="state" 
                                           value="<?php echo isset($user_data['state']) ? htmlspecialchars($user_data['state']) : ''; ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="postal_code" class="form-label">ZIP/Postal Code *</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                           value="<?php echo isset($user_data['zip']) ? htmlspecialchars($user_data['zip']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="country" class="form-label">Country *</label>
                                    <select class="form-select" id="country" name="country" required>
                                        <option value="">Select Country</option>
                                        <option value="MY" <?php echo (isset($user_data['country']) && $user_data['country'] == 'MY') ? 'selected' : ''; ?>>Malaysia</option>
                                        <option value="US" <?php echo (isset($user_data['country']) && $user_data['country'] == 'US') ? 'selected' : ''; ?>>United States</option>
                                        <option value="UK" <?php echo (isset($user_data['country']) && $user_data['country'] == 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
                                        <option value="SG" <?php echo (isset($user_data['country']) && $user_data['country'] == 'SG') ? 'selected' : ''; ?>>Singapore</option>
                                        <option value="AU" <?php echo (isset($user_data['country']) && $user_data['country'] == 'AU') ? 'selected' : ''; ?>>Australia</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($user_data['phone']) ? htmlspecialchars($user_data['phone']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4><i class="fas fa-credit-card me-2"></i> Payment Method</h4>
                        </div>
                        <div class="card-body">
                            <div class="payment-method-selection">
                                <div class="payment-method-option" data-method="credit_card">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                    <label class="form-check-label" for="credit_card">
                                        <i class="fab fa-cc-visa payment-icon"></i>
                                        <i class="fab fa-cc-mastercard payment-icon"></i>
                                        Credit / Debit Card
                                    </label>
                                </div>
                                
                                <div class="payment-method-option" data-method="paypal">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                    <label class="form-check-label" for="paypal">
                                        <i class="fab fa-paypal payment-icon"></i>
                                        PayPal
                                    </label>
                                </div>
                                
                                <div class="payment-method-option" data-method="bank_transfer">
                                    <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                    <label class="form-check-label" for="bank_transfer">
                                        <i class="fas fa-university payment-icon"></i>
                                        Bank Transfer
                                    </label>
                                </div>
                            </div>
                            
                            <div id="credit-card-fields">
                                <div class="mb-3">
                                    <label for="card_number" class="form-label">Card Number *</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                                        <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="expiry" class="form-label">Expiration Date (MM/YY) *</label>
                                        <input type="text" class="form-control" id="expiry" name="expiry" placeholder="MM/YY">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cvv" class="form-label">CVV *</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123">
                                            <span class="input-group-text" data-bs-toggle="tooltip" data-bs-title="3 or 4 digit security code usually found on the back of your card">
                                                <i class="fas fa-question-circle"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="paypal-fields" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    You will be redirected to PayPal to complete your payment.
                                </div>
                            </div>
                            
                            <div id="bank-transfer-fields" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Please make your payment to the following bank account:
                                    <br><br>
                                    <strong>Bank Name:</strong> Example Bank<br>
                                    <strong>Account Name:</strong> Graduation Shop<br>
                                    <strong>Account Number:</strong> 1234 5678 9012<br>
                                    <strong>Reference:</strong> Your email address<br>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-lock me-2"></i> Complete Order</button>
                        <p class="text-center mt-2">
                            <small class="text-muted">
                                By placing your order, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                            </small>
                        </p>
                    </div>
                </form>
            </div>
            
            <!-- Order Summary Column -->
            <div class="col-lg-5 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-shopping-cart me-2"></i> Order Summary</h4>
                    </div>
                    <div class="card-body">
                        <div class="order-items">
                            <?php foreach($_SESSION['cart'] as $item): ?>
                            <div class="order-item">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $item['imagepath']; ?>" alt="<?php echo htmlspecialchars($item['productname']); ?>">
                                    <div class="item-details">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['productname']); ?></h6>
                                        <p class="text-muted mb-0">Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                </div>
                                <div class="item-price">
                                    RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>RM<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span><?php echo ($shipping > 0) ? 'RM'.number_format($shipping, 2) : 'FREE'; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 pt-2 border-top">
                            <span class="fw-bold">Total:</span>
                            <span class="fw-bold">RM<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <?php if($cart_total < 50): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i> Add RM<?php echo number_format(50 - $cart_total, 2); ?> more to your cart to qualify for free shipping!
                        </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <h5>Shipping & Delivery</h5>
                            <p class="text-muted mb-0">
                                <i class="fas fa-shipping-fast me-2"></i> Estimated delivery: 3-5 business days
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Secure Checkout</h5>
                            <p class="text-muted mb-0">
                                <i class="fas fa-lock me-2"></i> Your payment information is processed securely.
                            </p>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <a href="cart.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Return to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle payment method selection
            $('.payment-method-option').click(function() {
                $('.payment-method-option').removeClass('selected');
                $(this).addClass('selected');
                
                // Check the radio button
                $(this).find('input[type="radio"]').prop('checked', true);
                
                // Show/hide payment fields
                const method = $(this).data('method');
                
                if (method === 'credit_card') {
                    $('#credit-card-fields').show();
                    $('#paypal-fields').hide();
                    $('#bank-transfer-fields').hide();
                } else if (method === 'paypal') {
                    $('#credit-card-fields').hide();
                    $('#paypal-fields').show();
                    $('#bank-transfer-fields').hide();
                } else if (method === 'bank_transfer') {
                    $('#credit-card-fields').hide();
                    $('#paypal-fields').hide();
                    $('#bank-transfer-fields').show();
                }
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Format credit card number with spaces
            $('#card_number').on('input', function() {
                const value = $(this).val().replace(/\s+/g, '');
                if (value.length > 0) {
                    $(this).val(value.match(/.{1,4}/g).join(' '));
                }
            });
            
            // Format expiry date
            $('#expiry').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                $(this).val(value);
            });
            
            // Basic form validation
            $('#checkout-form').on('submit', function(e) {
                const paymentMethod = $('input[name="payment_method"]:checked').val();
                
                if (paymentMethod === 'credit_card') {
                    const cardNumber = $('#card_number').val().replace(/\s+/g, '');
                    const expiry = $('#expiry').val();
                    const cvv = $('#cvv').val();
                    
                    if (cardNumber.length !== 16 || !/^\d+$/.test(cardNumber)) {
                        alert('Please enter a valid 16-digit card number.');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (!expiry || !/^\d{2}\/\d{2}$/.test(expiry)) {
                        alert('Please enter a valid expiration date (MM/YY).');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (!cvv || !/^\d{3,4}$/.test(cvv)) {
                        alert('Please enter a valid CVV code.');
                        e.preventDefault();
                        return false;
                    }
                }
                
                return true;
            });
        });
    </script>