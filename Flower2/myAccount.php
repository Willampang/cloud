    <?php
    // File: myAccount.php
    session_start();
    $title = "My Account - Graduation Shop";
    include("includes/header.php");

    // Check if user is logged in
    if(!isset($_SESSION['first_name'])) {
        header("Location: login.php");
        exit();
    }

    // Include database connection (only once)
    include("includes/db_connect.php");

    // Initialize variables
    $user = null;
    $user_id = null;

    if (isset($_SESSION['first_name'])) {
        $first_name = $_SESSION['first_name']; // Get the user's first name from session

        // Prepare and execute the query to check if the user exists in the database
        $query = "SELECT * FROM users WHERE first_name = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $first_name);
            $stmt->execute();
            
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // User found, proceed with displaying account information
                $user = $result->fetch_assoc();
                $user_id = $user['id']; // Make sure this matches your column name for the user ID
            } else {
                // If the first name doesn't match any user in the database
                echo "No account found with the name: $first_name. Please log in.";
                header("Location: login.php");
                exit();
            }

            $stmt->close();
        } else {
            echo "Error preparing the query: " . $conn->error;
        }
    } else {
        // User is not logged in
        echo "Please log in to view your account.";
        header("Location: login.php");
        exit();
    }

    // Handle form submission for account update
    $update_success = false;
    $update_error = '';

    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_account'])) {
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $address = $conn->real_escape_string($_POST['address']);
        $city = $conn->real_escape_string($_POST['city']);
        $state = $conn->real_escape_string($_POST['state']);
        $zip = $conn->real_escape_string($_POST['zip']);
        $country = $conn->real_escape_string($_POST['country']);
        $phone = $conn->real_escape_string($_POST['phone']);
        
        $update_sql = "UPDATE users SET 
                    first_name = '$first_name', 
                    last_name = '$last_name', 
                    address = '$address', 
                    city = '$city', 
                    state = '$state', 
                    zip = '$zip', 
                    country = '$country', 
                    phone = '$phone' 
                    WHERE id = $user_id";
        
        if($conn->query($update_sql) === TRUE) {
            $update_success = true;
            // Refresh user data
            $refresh_query = "SELECT * FROM users WHERE id = $user_id";
            $result = $conn->query($refresh_query);
            $user = $result->fetch_assoc();
        } else {
            $update_error = "Error updating account: " . $conn->error;
        }
    }

  // Handle password change
$password_success = false;
$password_error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    // Current password verification removed
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($new_password == $confirm_password) {
        if(strlen($new_password) >= 6) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_sql = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            
            if($conn->query($password_sql) === TRUE) {
                $password_success = true;
            } else {
                $password_error = "Error updating password: " . $conn->error;
            }
        } else {
            $password_error = "New password must be at least 6 characters long";
        }
    } else {
        $password_error = "New passwords do not match";
    }
}
    // Get order history - Only execute if user_id is set
    $orders_result = false;
    if ($user_id) {
        $orders_sql = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
              FROM `order` o 
              LEFT JOIN order_item oi ON o.order_id = oi.order_id 
              WHERE o.user_id = $user_id 
              GROUP BY o.order_id 
              ORDER BY o.updated_at DESC";
        $orders_result = $conn->query($orders_sql);
        
        // Check for SQL error
        if (!$orders_result) {
            echo "Error in order query: " . $conn->error;
            // This might be happening if the tables don't exist yet
            // We'll create a dummy result object to avoid breaking the page
            $orders_result = new class {
                public $num_rows = 0;
                public function fetch_assoc() { return []; }
            };
        }
    }

    // View parameter to determine which section to display
    $view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
    ?>

    <link rel="stylesheet" href="css/account.css">
    <div class="page-header">
        <div class="container">
            <h1>My Account</h1>
        </div>
    </div>

    <div class="account-section">
        <div class="container" style="margin-top: -110px;">
            <div class="account-layout">
                <div class="account-sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user-circle fa-4x"></i>
                        </div>
                        <div class="user-details">
                            <h3><?php echo $user['first_name'] . ' ' . ($user['last_name'] ?? ''); ?></h3>
                            <p><?php echo $user['usergmail'] ?? ''; ?></p>
                        </div>
                    </div>
                    
                    <nav class="account-nav">
                        <ul>
                            <li class="<?php echo ($view == 'dashboard') ? 'active' : ''; ?>">
                                <a href="myAccount.php?view=dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            </li>
                            <li class="<?php echo ($view == 'orders') ? 'active' : ''; ?>">
                                <a href="myAccount.php?view=orders"><i class="fas fa-shopping-bag"></i> Orders</a>
                            </li>
                            <li class="<?php echo ($view == 'addresses') ? 'active' : ''; ?>">
                                <a href="myAccount.php?view=addresses"><i class="fas fa-map-marker-alt"></i> Addresses</a>
                            </li>
                            <li class="<?php echo ($view == 'account') ? 'active' : ''; ?>">
                                <a href="myAccount.php?view=account"><i class="fas fa-user"></i> Account Details</a>
                            </li>
                            <li class="<?php echo ($view == 'password') ? 'active' : ''; ?>">
                                <a href="myAccount.php?view=password"><i class="fas fa-lock"></i> Change Password</a>
                            </li>
                            <li>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                
                <div class="account-content">
                    <?php if($view == 'dashboard'): ?>
                        <div class="dashboard-section">
                            <h2>Dashboard</h2>
                            <p>Hello <strong><?php echo $user['first_name']; ?></strong>, welcome to your account dashboard!</p>
                            
                            <div class="dashboard-cards">
                                <div class="dashboard-card">
                                    <div class="card-icon">
                                        <i class="fas fa-shopping-bag fa-2x"></i>
                                    </div>
                                    <div class="card-content">
                                        <h3>Your Orders</h3>
                                        <p><?php echo $orders_result ? $orders_result->num_rows : 0; ?> orders placed</p>
                                        <a href="myAccount.php?view=orders" class="btn-link">View Orders</a>
                                    </div>
                                </div>
                                
                                <div class="dashboard-card">
                                    <div class="card-icon">
                                        <i class="fas fa-map-marker-alt fa-2x"></i>
                                    </div>
                                    <div class="card-content">
                                        <h3>Your Addresses</h3>
                                        <p>Manage your shipping addresses</p>
                                        <a href="myAccount.php?view=addresses" class="btn-link">Manage Addresses</a>
                                    </div>
                                </div>
                                
                                <div class="dashboard-card">
                                    <div class="card-icon">
                                        <i class="fas fa-user fa-2x"></i>
                                    </div>
                                    <div class="card-content">
                                        <h3>Account Details</h3>
                                        <p>Edit your account information</p>
                                        <a href="myAccount.php?view=account" class="btn-link">Edit Details</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="recent-orders">
                                <h3>Recent Orders</h3>
                                
                                <?php if($orders_result && $orders_result->num_rows > 0): ?>
                                    <div class="order-table">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date</th>
                                                    <th>Items</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $count = 0;
                                                while($order = $orders_result->fetch_assoc()): 
                                                    if($count >= 5) break; // Show only 5 most recent orders
                                                    $count++;
                                                ?>
                                                    <tr>
                                                        <td>#<?php echo $order['order_id']; ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($order['updated_at'])); ?></td>
                                                        <td><?php echo $order['item_count']; ?></td>
                                                        <td>RM<?php echo number_format($order['amount'], 2); ?></td>
                                                        <td><span class="order-status completed">Completed</span></td>
                                                        <td>
                                                            <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-secondary btn-small">View</a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <?php if($orders_result->num_rows > 5): ?>
                                        <div class="view-all-orders">
                                            <a href="myAccount.php?view=orders" class="btn-link">View All Orders</a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="no-orders">
                                        <p>You haven't placed any orders yet.</p>
                                        <a href="products.php" class="btn-secondary">Start Shopping</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif($view == 'orders'): ?>
                        <div class="orders-section">
                            <h2>Your Orders</h2>
                            
                            <?php if($orders_result && $orders_result->num_rows > 0): ?>
                                <div class="order-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($order = $orders_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td>#<?php echo $order['order_id']; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($order['updated_at'])); ?></td>
                                                    <td><?php echo $order['item_count']; ?></td>
                                                    <td>RM<?php echo number_format($order['amount'], 2); ?></td>
                                                    <td><span class="order-status completed">Completed</span></td>
                                                    <td>
                                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-secondary btn-small">View</a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-orders">
                                    <p>You haven't placed any orders yet.</p>
                                    <a href="products.php" class="btn-secondary">Start Shopping</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif($view == 'addresses'): ?>
                        <div class="addresses-section">
                            <h2>Your Addresses</h2>
                            
                            <?php if(!empty($user['address'])): ?>
                                <div class="address-card">
                                    <div class="address-type">Default Address</div>
                                    <div class="address-content">
                                        <p><strong><?php echo $user['first_name'] . ' ' . ($user['last_name'] ?? ''); ?></strong></p>
                                        <p><?php echo $user['address']; ?></p>
                                        <p><?php echo ($user['city'] ?? '') . ', ' . ($user['state'] ?? '') . ' ' . ($user['zip'] ?? ''); ?></p>
                                        <p><?php echo $user['country'] ?? ''; ?></p>
                                        <p>Phone: <?php echo $user['phone'] ?? ''; ?></p>
                                    </div>
                                    <div class="address-actions">
                                        <a href="myAccount.php?view=account" class="btn-secondary btn-small">Edit</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-addresses">
                                    <p>You haven't added any addresses yet.</p>
                                    <a href="myAccount.php?view=account" class="btn-secondary">Add Address</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif($view == 'account'): ?>
                        <div class="account-details-section">
                            <h2>Account Details</h2>
                            
                            <?php if($update_success): ?>
                                <div class="alert alert-success">Your account details have been updated successfully.</div>
                            <?php endif; ?>
                            
                            <?php if(!empty($update_error)): ?>
                                <div class="alert alert-error"><?php echo $update_error; ?></div>
                            <?php endif; ?>
                            
                            <form method="post" action="myAccount.php?view=account" class="account-form">
                                <div class="form-row">
                                    <div class="form-group half">
                                        <label for="first_name">First Name *</label>
                                        <input type="text" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required>
                                    </div>
                                    <div class="form-group half">
                                        <label for="last_name">Last Name *</label>
                                        <input type="text" id="last_name" name="last_name" value="<?php echo $user['last_name'] ?? ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" value="<?php echo $user['usergmail'] ?? ''; ?>" readonly>
                                    <small>Email address cannot be changed.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Street Address *</label>
                                    <input type="text" id="address" name="address" value="<?php echo $user['address'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group half">
                                        <label for="city">City *</label>
                                        <input type="text" id="city" name="city" value="<?php echo $user['city'] ?? ''; ?>">
                                    </div>
                                    <div class="form-group half">
                                        <label for="state">State *</label>
                                        <input type="text" id="state" name="state" value="<?php echo $user['state'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group half">
                                        <label for="zip">ZIP / Postal Code *</label>
                                        <input type="text" id="zip" name="zip" value="<?php echo $user['zip'] ?? ''; ?>">
                                    </div>
                                    <div class="form-group half">
                                        <label for="country">Country *</label>
                                        <select id="country" name="country">
                                            <option value="">Select Country</option>
                                            <option value="MY" <?php echo (isset($user['country']) && $user['country'] == 'MY') ? 'selected' : ''; ?>>Malaysia</option>
                                            <option value="US" <?php echo (isset($user['country']) && $user['country'] == 'US') ? 'selected' : ''; ?>>United States</option>
                                            <option value="CA" <?php echo (isset($user['country']) && $user['country'] == 'CA') ? 'selected' : ''; ?>>Canada</option>
                                            <option value="GB" <?php echo (isset($user['country']) && $user['country'] == 'GB') ? 'selected' : ''; ?>>United Kingdom</option>
                                            <option value="AU" <?php echo (isset($user['country']) && $user['country'] == 'AU') ? 'selected' : ''; ?>>Australia</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone *</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo $user['phone'] ?? ''; ?>">
                                </div>
                                
                                <button type="submit" name="update_account" class="btn-primary">Save Changes</button>
                            </form>
                            <?php elseif($view == 'password'): ?>
    <div class="password-section">
        <h2>Change Password</h2>
        
        <?php if($password_success): ?>
            <div class="alert alert-success">Your password has been changed successfully.</div>
        <?php endif; ?>
        
        <?php if(!empty($password_error)): ?>
            <div class="alert alert-error"><?php echo $password_error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="myAccount.php?view=password" class="password-form">
            <!-- Current password field removed -->
            
            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input type="password" id="new_password" name="new_password" required>
                <small>Password must be at least 6 characters long</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" name="change_password" class="btn-primary">Change Password</button>
        </form>
    </div>
<?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php 
    $conn->close();
    include("includes/footer.php"); 
    ?>