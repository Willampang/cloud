<?php
// File: admin_edit_product.php
session_start();
$title = "Edit Product - Admin Panel";
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
$product = null;

// Get product ID from URL
if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];
    
    // Get product information
    $sql = "SELECT * FROM product WHERE productid = $product_id";
    $result = $conn->query($sql);
    
    if($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        $message = "Product not found";
        $messageType = "error";
    }
} else {
    header("Location: admin_products.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $productname = $conn->real_escape_string($_POST['productname']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = $conn->real_escape_string($_POST['price']);
    $category = $conn->real_escape_string($_POST['category']);
    $stock_quantity = $conn->real_escape_string($_POST['stock_quantity']);
    
    // Handle image upload
    $image_path = $product['imagepath']; // Keep existing image path by default
    
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = 'image/';
        $file_name = basename($_FILES['product_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Check if file already exists
        if (file_exists($target_file) && $target_file != $image_path) {
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
        
        // Update product in database if no errors
        if(empty($message)) {
            $sql = "UPDATE product SET 
                    productname = '$productname',
                    description = '$description',
                    price = '$price',
                    category = '$category',
                    stock_quantity = '$stock_quantity',
                    imagepath = '$image_path'
                    WHERE productid = $product_id";
            
            if($conn->query($sql) === TRUE) {
                $message = "Product updated successfully!";
                $messageType = "success";
                
                // Refresh product data
                $result = $conn->query("SELECT * FROM product WHERE productid = $product_id");
                $product = $result->fetch_assoc();
            } else {
                $message = "Error updating product: " . $conn->error;
                $messageType = "error";
            }
        }
        }
        ?>
<link rel="stylesheet" href="css/manageProduct.css">
        <div class="container mt-4">
            <div class="row">
                <div class="col-md-12">
                    <h2>Edit Product</h2>
                    <a href="admin_products.php" class="btn btn-secondary mb-3">Back to Products</a>
                    
                    <?php if(!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType == 'success' ? 'success' : 'danger'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($product): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="productname">Product Name:</label>
                                <input type="text" class="form-control" id="productname" name="productname" value="<?php echo htmlspecialchars($product['productname']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description:</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Price:</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category:</label>
                                <select class="form-control" id="category" name="category" required>
                                <?php
                                // Get all categories
                                $cat_sql = "SELECT * FROM product ORDER BY category";
                                $cat_result = $conn->query($cat_sql);

                                while($cat = $cat_result->fetch_assoc()) {
                                    $selected = ($cat['category'] == $product['category']) ? 'selected' : '';
                                    echo "<option value='{$cat['category']}' $selected>{$cat['category']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                            
                            <div class="form-group">
                                <label for="stock_quantity">Stock Quantity:</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_image">Product Image:</label>
                                <?php if(!empty($product['imagepath'])): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($product['imagepath']); ?>" alt="Current product image" style="max-width: 200px;">
                                        <p class="text-muted">Current image</p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control-file" id="product_image" name="product_image">
                                <small class="form-text text-muted">Leave empty to keep current image</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-danger">Product not found!</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include("includes/footer.php"); ?>