<?php
// File: admin_add_product.php
session_start();
$title = "Add Product - Admin Panel";
include("includes/header.php");

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Include database connection
include("includes/db_connect.php");

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $productname = $conn->real_escape_string($_POST['productname']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = $conn->real_escape_string($_POST['price']);
    $category = $conn->real_escape_string($_POST['category']);
    $stock_quantity = $conn->real_escape_string($_POST['stock_quantity']);
    
    // Handle image upload
    $image_path = '';
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = 'image/';
        $file_name = basename($_FILES['product_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Check if file already exists
        if (file_exists($target_file)) {
            $message = "Sorry, file already exists.";
            $messageType = "error";
        } else {
            // Upload file
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            } else {
                $message = "Sorry, there was an error uploading your file.";
                $messageType = "error";
            }
        }
    }
    
    // Insert product into database
    if(empty($message)) {
        $sql = "INSERT INTO product (productname, description, price, imagepath, category, stock_quantity) 
                VALUES ('$productname', '$description', '$price', '$image_path', '$category', '$stock_quantity')";
        
        if($conn->query($sql) === TRUE) {
            $message = "Product added successfully";
            $messageType = "success";
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
            $messageType = "error";
        }
    }
}
?>

<link rel="stylesheet" href="css/admin.css">
<link rel="stylesheet" href="css/manageProduct.css">    
    <div class="admin-content">
        <div class="admin-header">
            <h1>Add New Product</h1>
            <div class="admin-actions">
                <a href="admin_products.php" class="btn-outline"><i class="fas fa-arrow-left"></i> Back to Products</a>
            </div>
        </div>
        
        <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label for="productname">Product Name *</label>
                    <input type="text" id="productname" name="productname" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="6"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="price">Price (RM) *</label>
                        <input type="number" id="price" name="price" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Balloons">Balloons</option>
                        <option value="Bears">Bears</option>
                        <option value="Flowers">Flowers</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity *</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" required>
                </div>
                
                <div class="form-group">
                    <label for="product_image">Product Image</label>
                    <input type="file" id="product_image" name="product_image" accept="image/*">
                    <small>Recommended size: 600x600 pixels</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $conn->close(); ?>
