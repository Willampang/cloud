<?php
// File: admin_orders.php
session_start();
$title = "Orders - Admin Panel";
include("includes/header.php");

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include("includes/db_connect.php");

// Handle customer deletion
if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];

    // Optional: You can add logic to prevent deleting yourself or admin users
    $conn->query("DELETE FROM users WHERE id = $delete_id");

    // Redirect to avoid resubmission
    header("Location: admin_customers.php");
    exit();
}
// Handle bulk status update if submitted
if(isset($_POST['bulk_action']) && isset($_POST['order_ids']) && !empty($_POST['order_ids'])) {
    $new_status = $conn->real_escape_string($_POST['bulk_action']);
    $order_ids = implode(',', array_map('intval', $_POST['order_ids']));
    
    if(in_array($new_status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        $update_sql = "UPDATE `order` SET order_status = '$new_status' WHERE order_id IN ($order_ids)";
        if($conn->query($update_sql)) {
            $message = "Orders updated successfully";
            $message_type = "success";
        } else {
            $message = "Error updating orders: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Filter orders by status
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$status_condition = '';

if(!empty($status_filter)) {
    $status_condition = " WHERE o.order_status = '$status_filter'";
}

// Search filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
if(!empty($search)) {
    $search_condition = " WHERE (o.order_id LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%')";
    $status_condition = empty($status_condition) ? $search_condition : $status_condition . " AND (o.order_id LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%')";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_records_result = $conn->query("SELECT COUNT(*) as count FROM `order` o JOIN users u ON o.user_id = u.id" . $status_condition);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $records_per_page);

// Get orders with calculated total amount
$sql = "SELECT o.*, u.first_name, u.last_name,
        (SELECT SUM(oi.price * oi.quantity) FROM order_item oi WHERE oi.order_id = o.order_id) as total_amount
        FROM `order` o 
        JOIN users u ON o.user_id = u.id" . 
        $status_condition . 
        " ORDER BY o.updated_at DESC LIMIT $offset, $records_per_page";

$orders = $conn->query($sql);

// Get order statuses for filter
$statuses = $conn->query("SELECT DISTINCT order_status FROM `order`");
?>
<link rel="stylesheet" href="css/admin_orders.css">
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Order Management</h2>
            <p>View and manage customer orders.</p>
            
            <?php if(isset($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form method="GET" action="" class="mb-0">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search by Order ID or Customer Name" value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <?php if(!empty($search)): ?>
                                        <a href="admin_orders.php<?php echo !empty($status_filter) ? '?status='.$status_filter : ''; ?>" class="btn btn-secondary">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="" class="mb-0">
                                <div class="input-group">
                                    <select name="status" class="form-control">
                                        <option value="">All Orders</option>
                                        <?php if($statuses->num_rows > 0): ?>
                                            <?php while($status = $statuses->fetch_assoc()): ?>
                                                <option value="<?php echo $status['order_status']; ?>" <?php echo $status_filter == $status['order_status'] ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($status['order_status']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <?php if(!empty($status_filter)): ?>
                                        <a href="admin_orders.php<?php echo !empty($search) ? '?search='.$search : ''; ?>" class="btn btn-secondary">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>    
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Shipping Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($orders->num_rows > 0): ?>
                                <?php while($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="order_ids[]" value="<?php echo $order['order_id']; ?>" class="order-checkbox">
                                        </td>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['updated_at'])); ?></td>
                                        <td>RM<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo ($order['order_status'] == 'delivered') ? 'bg-success' : 
                                                    (($order['order_status'] == 'processing') ? 'bg-warning text-dark' : 
                                                    (($order['order_status'] == 'cancelled') ? 'bg-danger' : 
                                                    (($order['order_status'] == 'shipped') ? 'bg-info' : 'bg-secondary'))); 
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if($order['shipping_address']) {
                                                echo nl2br(htmlspecialchars(substr($order['shipping_address'], 0, 30) . (strlen($order['shipping_address']) > 30 ? '...' : '')));
                                            } else {
                                                echo '<span class="text-muted">No address</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="admin_view_order.php?id=<?php echo $order['order_id']; ?>" style="background-color:blue; color:white" class="btn btn-sm btn-info">View</a>  
                                            <a href="admin_orders.php?delete=<?php echo $customer['id']; ?>" 
                                        class="btn-delete btn btn-sm btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this customer?');">
                                        Delete
                                        </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Select all functionality
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    const bulkActionBtn = document.getElementById('bulk-action-btn');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    
    // Enable/disable bulk action button
    bulkActionBtn.disabled = !this.checked;
});

// Enable bulk action button if at least one checkbox is checked
const orderCheckboxes = document.querySelectorAll('.order-checkbox');
orderCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        bulkActionBtn.disabled = checkedBoxes.length === 0;
    });
});

// Confirm bulk status change
document.getElementById('orders-form').addEventListener('submit', function(e) {
    const action = document.querySelector('select[name="bulk_action"]').value;
    if (!action) {
        e.preventDefault();
        alert('Please select an action to perform.');
        return;
    }
    
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Please select at least one order.');
        return;
    }
    
    if (!confirm('Are you sure you want to update the status of ' + checkedBoxes.length + ' selected orders?')) {
        e.preventDefault();
    }
});
</script>

<?php 
$conn->close();
include("includes/footer.php"); 
?>