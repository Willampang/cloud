<?php
// File: admin_customers.php
session_start();
$title = "Customers - Admin Panel";
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


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
if(!empty($search)) {
    $search_condition = " WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR usergmail LIKE '%$search%' OR phone LIKE '%$search%'";
}

// Get total number of records
$total_records_result = $conn->query("SELECT COUNT(*) as count FROM users" . $search_condition);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $records_per_page);

// Get customers
$sql = "SELECT * FROM users" . $search_condition . " ORDER BY id DESC LIMIT $offset, $records_per_page";
$customers = $conn->query($sql);
?>

<link rel="stylesheet" href="css/admin_customer.css">
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Customer Management</h2>
            <p>View and manage customer information.</p>
            
            <!-- Search form -->
            <form method="GET" action="" class="mb-4">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search customers..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="admin_customers.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($customers->num_rows > 0): ?>
                            <?php while($customer = $customers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $customer['id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['usergmail']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td>
                                        <a href="admin_view_customer.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info">View Details</a>
                                        <a href="admin_orders.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">Orders</a>
                                        <a href="admin_customers.php?delete=<?php echo $customer['id']; ?>" 
                                        class="btn-delete btn btn-sm btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this customer?');">
                                        Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No customers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
$conn->close();
include("includes/footer.php"); 
?>