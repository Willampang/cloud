<?php
// File: admin_view_customer.php
session_start();
$title = "Customer Details - Admin Panel";
include("includes/header.php");

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include("includes/db_connect.php");

// Get customer ID from URL
if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $customer_id = $_GET['id'];
    
    // Get customer information
    $sql = "SELECT * FROM users WHERE id = $customer_id";
    $result = $conn->query($sql);
    
    if($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        
        // Get customer address
        $address_sql = "SELECT * FROM users WHERE id = $customer_id";
        $address_result = $conn->query($address_sql);
        $addresses = [];
        
        if($address_result->num_rows > 0) {
            while($row = $address_result->fetch_assoc()) {
                $addresses[] = $row;
            }
        }
        
        $order_stats = $conn->query("SELECT COUNT(*) as order_count, SUM(amount) as total_spent 
        FROM `order` WHERE user_id = $customer_id")->fetch_assoc();

    } else {
        header("Location: admin_customers.php");
        exit();
    }
} else {
    header("Location: admin_customers.php");
    exit();
}
?>
<link rel="stylesheet" href="css/adminViewCustomer.css">

<div class="container mt-4" style="margin-top: 20px;margin-bottom: 20px;">
    <div class="row">
        <div class="col-md-12">
            <h2>Customer Details</h2>
            <a href="admin_customers.php" class="btn btn-secondary mb-3">Back to Customers</a>
            
            <div class="row">
                <!-- Customer Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Customer Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <tr>
                                    <th width="30%">ID</th>
                                    <td><?php echo $customer['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?php echo htmlspecialchars($customer['usergmail']); ?></td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Statistics -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Customer Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-6 mb-3">
                                    <h2 class="text-primary"><?php echo $order_stats['order_count'] ?: 0; ?></h2>
                                    <p class="text-muted">Total Orders</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h2 class="text-success">RM<?php echo number_format($order_stats['total_spent'] ?: 0, 2); ?></h2>
                                    <p class="text-muted">Total Spent</p>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <a href="admin_view_order.php?id=<?php echo $customer_id; ?>" class="btn btn-primary">View Orders</a>
                                <?php if(isset($_SESSION['advanced_features']) && $_SESSION['advanced_features']): ?>
                                <a href="admin_chat.php?customer_id=<?php echo $customer_id; ?>" class="btn btn-info">Chat with Customer</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Addresses -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Customer Addresses</h5>
                        </div>
                        <div class="card-body">
                            <?php if(count($addresses) > 0): ?>
                                <?php foreach($addresses as $address): ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <p class="card-text">
                                                <?php echo htmlspecialchars($address['address']); ?><br>
                                                <?php if(!empty($address['address'])): echo htmlspecialchars($address['address']) . '<br>'; endif; ?>
                                                <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['zip']); ?><br>
                                                <?php echo htmlspecialchars($address['country']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No addresses found for this customer.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include("includes/footer.php"); 
?>