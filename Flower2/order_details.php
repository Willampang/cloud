<?php
session_start();
include("includes/db_connect.php");
include("includes/header.php");
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid order ID.");
}

$order_id = $_GET['id'];
$user_query = "SHOW TABLES LIKE 'users'";
$result = $conn->query($user_query);

if($result->num_rows > 0) {
    // Table exists, check its structure
    $struct_query = "DESCRIBE users";
    $struct_result = $conn->query($struct_query);
    $columns = [];
    
    while($row = $struct_result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}

// Try using the 'first_name' to find the user (since we see 'bb' in session)
$user_check_sql = "SELECT * FROM users WHERE first_name = ?";
$user_stmt = $conn->prepare($user_check_sql);
$user_stmt->bind_param("s", $_SESSION['first_name']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $user_id = $user_data['id']; // Get the actual user ID from the database
} else {
    // Try another approach - list all users for debugging
    $all_users = $conn->query("SELECT * FROM users LIMIT 5");
    if($all_users->num_rows > 0) {
        echo "First 5 users in the database:<br><ul>";
        while($user = $all_users->fetch_assoc()) {
            echo "<li>ID: {$user['id']}, Name: {$user['first_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "Users table is empty.";
    }
    
    echo "</div>";
    die("Cannot continue without valid user ID.");
}

// Fetch the order details for the logged-in user
$order_sql = "SELECT * FROM `order` WHERE order_id = ? AND user_id = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();

if($order_result->num_rows > 0) {
    $order_data = $order_result->fetch_assoc();

    // Fetch the items in the order
    $items_sql = "SELECT oi.*, p.productname FROM order_item oi JOIN product p ON oi.product_id = p.productid WHERE oi.order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();

    $order_items = [];
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
} else {
    die("Order not found for this user.");
}

$stmt->close();
$user_stmt->close();
$items_stmt->close();
$conn->close();
?>

<body>
    <div class="container">
        <h1>Order Details - Order #<?php echo $order_data['order_id']; ?></h1>
        
        <?php 
        $status_class = 'status-' . strtolower($order_data['order_status']);
        ?>
        
        <p>Status: <span class="order-status <?php echo $status_class; ?>"><?php echo ucfirst($order_data['order_status']); ?></span></p>
        <p>Order Date: <?php echo date("F j, Y", strtotime($order_data['updated_at'])); ?></p>
        <p>Shipping Address: <?php echo htmlspecialchars($order_data['shipping_address']); ?></p>

        <h2>Order Items</h2>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['productname']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>RM<?php echo number_format($item['price'], 2); ?></td>
                    <td>RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Total Amount: RM<?php echo number_format($order_data['amount'], 2); ?></h3>

        <div class="footer">
            <a href="myAccount.php" class="btn">Back to My Account</a>
        </div>
    </div>
    <style>
        .order-table {
  width: 100%;
  border-collapse: collapse;
  margin: 20px 0;
}

.order-table th {
  background-color: #3a6ea5;
  color: white;
  text-align: left;
  padding: 12px;
  font-weight: 500;
}

.order-table td {
  padding: 12px;
  border-bottom: 1px solid #ddd;
}

.order-table tr:last-child td {
  border-bottom: none;
}

.order-table tr:nth-child(even) {
  background-color: #f2f2f2;
}

.footer {
  margin-top: 30px;
  text-align: center;
  margin-bottom: 20px;
}

.btn {
  display: inline-block;
  background-color: #3a6ea5;
  color: white;
  padding: 12px 25px;
  text-decoration: none;
  border-radius: 5px;
  transition: all 0.3s ease;
  font-weight: 500;
  border: none;
  cursor: pointer;
}

.btn:hover {
  background-color: #2d5a8e;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.order-status {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
  text-transform: uppercase;
}

.status-pending {
  background-color: #ffecb3;
  color: #856404;
}

.status-processing {
  background-color: #b3e5fc;
  color: #0277bd;
}

.status-shipped {
  background-color: #c8e6c9;
  color: #2e7d32;
}

.status-delivered {
  background-color: #dcedc8;
  color: #33691e;
}

.status-cancelled {
  background-color: #ffcdd2;
  color: #c62828;
}

/* Responsive design */
@media (max-width: 768px) {
  .container {
    padding: 15px;
    margin: 20px 10px;
  }
}
    </style>
<?php
include("includes/footer.php");
?>