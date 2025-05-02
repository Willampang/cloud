<?php
// File: register.php
session_start();
$title = "Register - Graduation Shop";
include("includes/header.php");

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include("includes/db_connect.php");
    
    // Get form data
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // New address fields
    $address = $conn->real_escape_string($_POST['address']);
    $city = $conn->real_escape_string($_POST['city']);
    $state = $conn->real_escape_string($_POST['state']);
    $zip = $conn->real_escape_string($_POST['zip']);
    $country = $conn->real_escape_string($_POST['country']);
    $phone = $conn->real_escape_string($_POST['phone']);
    
    // Validate form data
    if(empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All required fields must be filled out";
    } elseif($password != $confirm_password) {
        $error = "Passwords do not match";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if email already exists
        $check_sql = "SELECT * FROM users WHERE usergmail = '$email'";
        $check_result = $conn->query($check_sql);
        
        if($check_result->num_rows > 0) {
            $error = "Email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user data including address
            $sql = "INSERT INTO USERS (first_name, last_name, usergmail, password, address, city, state, zip, country, phone) 
                    VALUES ('$first_name', '$last_name', '$email', '$hashed_password', '$address', '$city', '$state', '$zip', '$country', '$phone')";
            
            if($conn->query($sql) === TRUE) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
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
            <h1>Create an Account</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="auth-form">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-group half">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group half">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                        <small>Password must be at least 6 characters long</small>
                    </div>
                    
                    <div class="form-group half">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <h3>Address Information</h3>
                <div class="form-group">
                    <label for="address">Street Address</label>
                    <input type="text" id="address" name="address">
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city">
                    </div>
                    <div class="form-group half">
                        <label for="state">State/Province</label>
                        <input type="text" id="state" name="state">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="zip">ZIP / Postal Code</label>
                        <input type="text" id="zip" name="zip">
                    </div>
                    <div class="form-group half">
                        <label for="country">Country</label>
                        <select id="country" name="country">
                            <option value="">Select Country</option>
                            <option value="MY">Malaysia</option>
                            <option value="US">United States</option>
                            <option value="CA">Canada</option>
                            <option value="GB">United Kingdom</option>
                            <option value="AU">Australia</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                
                <button type="submit" class="btn-primary btn-full">Register</button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>