<?php
// File: admin_login.php
session_start();
$title = "Admin Login - Graduation Shop";
include("includes/header.php");

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include("includes/db_connect.php");
    
    // Get form data
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Check if it's the admin account
    if($email === 'admin@gmail.com' && $password === '123') {
        // Set admin session
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_email'] = $email;
        
        // Redirect to admin dashboard
        header("Location: admin.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
    
    $conn->close();
}
?>
<link rel="stylesheet" href="css/auth.css">
<div class="auth-section">
    <div class="container">
        <div class="auth-container">
            <h1>Admin Login</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary btn-full">Login</button>
            </form>
            
            <div class="auth-links">
                <p><a href="index.php">Return to Shop</a></p>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>