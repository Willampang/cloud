<?php
session_start();
$title = "Order Confirmation - Graduation Shop";
include("includes/header.php");

// Check if we have order confirmation data
if(!isset($_SESSION['last_order_id'])) {
    // Redirect to home if no order was placed
    header("Location: index.php");
    exit();
}

$order_id = $_SESSION['last_order_id'];
$payment_method = isset($_SESSION['payment_method']) ? $_SESSION['payment_method'] : '';
$order_total = isset($_SESSION['order_total']) ? $_SESSION['order_total'] : 0;
$success_message = isset($_SESSION['order_success_message']) ? $_SESSION['order_success_message'] : 'Your order has been placed successfully!';
?>

<link rel="stylesheet" href="css/checkout.css">
<div class="checkout-container">
    <div class="checkout-header">
        <h1 class="mb-3">Order Confirmation</h1>
        <div class="checkout-steps">
            <div class="step active">
                <span class="step-number">1</span>
                <p>Shopping Cart</p>
            </div>
            <div class="step active">
                <span class="step-number">2</span>
                <p>Checkout</p>
            </div>
            <div class="step active">
                <span class="step-number">3</span>
                <p>Confirmation</p>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center mt4 ">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center">
                    <div class="confirmation-icon">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    
                    <h2 class="mt-4">Thank You for Your Order!</h2>
                    <p class="lead"><?php echo $success_message; ?></p>
                    
                    <div class="order-details mt-4">
                        <div class="row">
                            <div class="col-md-6 text-md-end">
                                <p><strong>Order Number:</strong></p>
                            </div>
                            <div class="col-md-6 text-md-start">
                                <p>#<?php echo $order_id; ?></p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 text-md-end">
                                <p><strong>Payment Method:</strong></p>
                            </div>
                            <div class="col-md-6 text-md-start">
                                <p>
                                <?php 
                                    switch($payment_method) {
                                        case 'credit_card':
                                            echo 'Credit/Debit Card';
                                            break;
                                        case 'paypal':
                                            echo 'PayPal';
                                            break;
                                        case 'bank_transfer':
                                            echo 'Bank Transfer';
                                            break;
                                        default:
                                            echo 'Online Payment';
                                    }
                                ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 text-md-end">
                                <p><strong>Total Amount:</strong></p>
                            </div>
                            <div class="col-md-6 text-md-start">
                                <p>RM<?php echo number_format($order_total, 2); ?></p>
                            </div>
                        </div>
                    </div>       
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Clear the session variables related to order after displaying the confirmation page
unset($_SESSION['last_order_id']);
unset($_SESSION['payment_method']);
unset($_SESSION['order_total']);
unset($_SESSION['order_success_message']);

include("includes/footer.php");
?>