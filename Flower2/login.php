<?php
// File: login.php
session_start();
$title = "Login - Graduation Shop";
include("includes/header.php");

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include("includes/db_connect.php");
    
    // Get form data
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Check if it's the admin account first
    if($email === 'admin@gmail.com' && $password === '123') {
        // Set admin session
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_email'] = $email;
        $_SESSION['is_admin'] = true;
        
        // Redirect to admin dashboard
        header("Location: admin.php");
        exit();
    } 
    // If not admin, check regular user credentials
    else {
        // Validate form data
        if(empty($email) || empty($password)) {
            $error = "Email and password are required";
        } else {
            // Check if email exists
            $sql = "SELECT * FROM users WHERE usergmail = '$email'";
            $result = $conn->query($sql);
            
            if($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if(password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['is_admin'] = false;
                    
                    // Redirect to home page
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Invalid email or password";
                }
            } else {
                $error = "Invalid email or password";
            }
        }
    }
    
    $conn->close();
}
?>
<link rel="stylesheet" href="css/auth.css">
<div class="auth-section">
    <div class="container">
        <div class="auth-container">
            <h1>Login</h1>
            
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
                
                <div class="form-remember">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                
                <button type="submit" class="btn-primary btn-full">Login</button>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>