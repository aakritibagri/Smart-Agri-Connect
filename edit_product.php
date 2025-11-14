<?php
session_start();
include("db.php");

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
if (!isset($_GET['id'])) {
    header("Location: dashboard_farmer.php");
    exit();
}

$product_id = intval($_GET['id']);
$msg = "";

// Fetch product and verify ownership
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND farmer_email = ?");
$stmt->bind_param("is", $product_id, $email);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['msg'] = "❌ Product not found or access denied.";
    header("Location: dashboard_farmer.php");
    exit();
}

// Handle form submit
if (isset($_POST['update_product'])) {
    $product_name = trim($_POST['product_name']);
    $price = floatval($_POST['price']);
    $quantity_kg = floatval($_POST['quantity_kg']);
    $category = trim($_POST['category']);

    if ($price <= 0 || $quantity_kg <= 0) {
        $msg = "❌ Price and Quantity must be positive numbers.";
    } elseif (empty($category)) {
        $msg = "❌ Please select a category.";
    } else {
        $image_name = $product['image']; // keep old unless changed

        // Check for new image
        if (!empty($_FILES['image']['name'])) {
            $image_tmp = $_FILES['image']['tmp_name'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_mime = finfo_file($finfo, $image_tmp);
            finfo_close($finfo);

            if (!in_array($file_mime, $allowed_types)) {
                $msg = "❌ Only JPG, PNG, GIF images are allowed.";
            } else {
                $safe_name = uniqid() . "-" . preg_replace("/[^a-zA-Z0-9\.\-\_]/", "", basename($_FILES['image']['name']));
                $target_dir = __DIR__ . "/uploads/" . $safe_name;
                if (move_uploaded_file($image_tmp, $target_dir)) {
                    $image_name = $safe_name;
                } else {
                    $msg = "❌ Failed to upload new image.";
                }
            }
        }

        if (empty($msg)) {
            // Update product
            $stmt = $conn->prepare("UPDATE products SET product_name = ?, price = ?, quantity_kg = ?, category = ?, image = ? WHERE id = ? AND farmer_email = ?");
            $stmt->bind_param("sddssis", $product_name, $price, $quantity_kg, $category, $image_name, $product_id, $email);
            if ($stmt->execute()) {
                $_SESSION['msg'] = "✅ Product updated successfully!";
                $stmt->close();
                header("Location: dashboard_farmer.php");
                exit();
            } else {
                $msg = "❌ Database error: Could not update product.";
                $stmt->close();
            }
        }
    }
}

// Example categories you might want to use
$categories = [
    "Fruits", "Vegetables", "Foodgrains", "Oils & Ghee","Other"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Product - Smart Agri Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-100 min-h-screen flex flex-col items-center p-4">

<header class="bg-green-700 text-white p-4 w-full max-w-3xl rounded-md flex justify-between items-center">
    <h1 class="text-xl font-bold">Edit Product</h1>
    <a href="dashboard_farmer.php" class="bg-white text-green-700 px-3 py-1 rounded hover:bg-green-200">Back</a>
</header>

<div class="max-w-3xl w-full bg-white shadow-md mt-6 p-6 rounded-xl">
    <?php if (!empty($msg)) echo "<p class='text-center text-red-600 mb-3'>".htmlspecialchars($msg)."</p>"; ?>

    <form method="POST" enctype="multipart/form-data" class="grid gap-3">
        <input type="text" name="product_name" placeholder="Product Name"
               value="<?php echo htmlspecialchars($product['product_name']); ?>" required
               class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400" />
        <select name="category" required class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400">
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat; ?>" <?php if ($product['category'] == $cat) echo "selected"; ?>>
                <?php echo $cat; ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="number" step="0.01" name="price" placeholder="Total Price (₹)"
               value="<?php echo htmlspecialchars($product['price']); ?>" required min="0.01"
               class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400" />
        <input type="number" step="0.01" name="quantity_kg" placeholder="Available Quantity (kg)"
               value="<?php echo htmlspecialchars($product['quantity_kg']); ?>" required min="0.01"
               class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400" />
        <p>Current Image:</p>
        <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Current Image" class="w-40 h-40 object-cover mb-3 rounded" />
        <label class="block">Change Image (optional):</label>
        <input type="file" name="image" accept="image/*" class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400" />
        <button type="submit" name="update_product"
                class="bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">Update Product</button>
    </form>
</div>

</body>
</html>
