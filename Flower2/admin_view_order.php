<?php
// File: admin_view_order.php
session_start();
$title = "View Order - Admin Panel";
include("includes/header.php");

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include("includes/db_connect.php");

// Check if order ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Handle status update if form is submitted
if(isset($_POST['update_status']) && isset($_POST['new_status'])) {
    $new_status = $conn->real_escape_string($_POST['new_status']);
    
    // Validate status
    if(in_array($new_status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        $update_sql = "UPDATE `order` SET order_status = '$new_status' WHERE order_id = $order_id";
        
        if($conn->query($update_sql)) {
            $message = "Order status updated successfully";
            $message_type = "success";
        } else {
            $message = "Error updating order status: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Get order details
$sql = "SELECT o.*, u.first_name, u.last_name, u.usergmail, u.phone, u.address, u.city, u.state, u.zip, u.country,
        (SELECT SUM(oi.price * oi.quantity) FROM order_item oi WHERE oi.order_id = o.order_id) as total_amount
        FROM `order` o
        JOIN users u ON o.user_id = u.id
        WHERE o.order_id = $order_id";

$result = $conn->query($sql);

if($result->num_rows == 0) {
    header("Location: admin_orders.php");
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$items_sql = "SELECT oi.*, p.productname, p.imagepath
              FROM order_item oi
              JOIN product p ON oi.product_id = p.productid
              WHERE oi.order_id = $order_id";

$items_result = $conn->query($items_sql);
?>
<link rel="stylesheet" href="css/viewOrder.css">
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-12">
            <a href="admin_orders.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Orders</a>
            <div class="d-flex justify-content-between align-items-center">
                <h2>Order #<?php echo $order_id; ?></h2>
            </div>
        </div>
    </div>

    <?php if(isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Order Details -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <h5>Order Details</h5>
                    <span class="badge <?php 
                        echo ($order['order_status'] == 'delivered') ? 'bg-success' : 
                            (($order['order_status'] == 'processing') ? 'bg-warning text-dark' : 
                            (($order['order_status'] == 'cancelled') ? 'bg-danger' : 
                            (($order['order_status'] == 'shipped') ? 'bg-info' : 'bg-secondary'))); 
                    ?> fs-6">
                        <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['updated_at'])); ?></p>
                            <p><strong>Total Amount:</strong> RM<?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                        <div class="col-md-6">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="new_status" class="form-label">Update Status</label>
                                    <select name="new_status" id="new_status" class="form-select">
                                        <option value="pending" <?php echo ($order['order_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo ($order['order_status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo ($order['order_status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo ($order['order_status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo ($order['order_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Order Items</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($items_result->num_rows > 0): ?>
                                <?php while($item = $items_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if(!empty($item['imagepath'])): ?>
                                                    <img src="<?php echo $item['imagepath']; ?>" alt="<?php echo htmlspecialchars($item['productname']); ?>" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <?php echo htmlspecialchars($item['productname']); ?>
                                                    <br>
                                                    <small class="text-muted">Product ID: <?php echo $item['product_id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>RM<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>RM<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No items found for this order</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
            <!-- Shipping Address -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Shipping Address</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($order['shipping_address'])): ?>
                        <address>
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </address>
                    <?php else: ?>
                        <p>Custom shipping address not provided.</p>
                        <address>
                            <?php echo htmlspecialchars($order['address']); ?><br>
                            <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['zip']); ?><br>
                            <?php echo htmlspecialchars($order['country']); ?>
                        </address>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php 
$conn->close();
include("includes/footer.php"); 
?>