<?php
session_start();
include("db.php");

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$msg = "";

// Handle profile image upload and removal
if (isset($_POST['upload_profile'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $img_tmp = $_FILES['profile_image']['tmp_name'];
        $img_name = $_FILES['profile_image']['name'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $img_tmp);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $msg = "❌ Only JPG, PNG, GIF allowed.";
        } else {
            $safe_name = uniqid().'-'.preg_replace("/[^a-zA-Z0-9\.\-\_]/", "", basename($img_name));
            $path = __DIR__."/uploads/".$safe_name;
            if (move_uploaded_file($img_tmp, $path)) {
                // Remove old image if exists
                $stmt_old = $conn->prepare("SELECT profile_image FROM farmers WHERE email=?");
                $stmt_old->bind_param("s", $email);
                $stmt_old->execute();
                $stmt_old->bind_result($old_img);
                $stmt_old->fetch();
                $stmt_old->close();
                if ($old_img && file_exists(__DIR__."/uploads/".$old_img)) unlink(__DIR__."/uploads/".$old_img);

                $stmt = $conn->prepare("UPDATE farmers SET profile_image=? WHERE email=?");
                $stmt->bind_param("ss", $safe_name, $email);
                $stmt->execute();
                $stmt->close();
                $msg = "✅ Profile image updated!";
            } else {
                $msg = "❌ Upload failed!";
            }
        }
    }
}

if (isset($_POST['remove_profile'])) {
    $stmt_old = $conn->prepare("SELECT profile_image FROM farmers WHERE email=?");
    $stmt_old->bind_param("s", $email);
    $stmt_old->execute();
    $stmt_old->bind_result($old_img);
    $stmt_old->fetch();
    $stmt_old->close();
    if ($old_img && file_exists(__DIR__."/uploads/".$old_img)) unlink(__DIR__."/uploads/".$old_img);
    $stmt = $conn->prepare("UPDATE farmers SET profile_image=NULL WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
    $msg = "✅ Profile image removed!";
}

// Fetch farmer details (with image)
$stmt = $conn->prepare("SELECT name, email, profile_image FROM farmers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$farmer = $result->fetch_assoc();
$stmt->close();

// Define allowed categories
$categories = ["Fruits", "Vegetables", "Foodgrains", "Oils & Ghee", "Other"];

// ADD PRODUCT FORM processing
if (isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name']);
    $price = floatval($_POST['price']);
    $quantity_kg = floatval($_POST['quantity_kg']);
    $category = trim($_POST['category']);

    if ($price <= 0 || $quantity_kg <= 0) {
        $msg = "❌ Price and Quantity must be positive numbers.";
    } elseif (empty($category)) {
        $msg = "❌ Please select a category.";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_tmp = $_FILES['image']['tmp_name'];
            $image_name = $_FILES['image']['name'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_mime = finfo_file($finfo, $image_tmp);
            finfo_close($finfo);

            if (!in_array($file_mime, $allowed_types)) {
                $msg = "❌ Only JPG, PNG, GIF images are allowed.";
            } else {
                $safe_name = uniqid() . "-" . preg_replace("/[^a-zA-Z0-9\.\-\_]/", "", basename($image_name));
                $target_dir = __DIR__ . "/uploads/" . $safe_name;

                if (move_uploaded_file($image_tmp, $target_dir)) {
                    $stmt = $conn->prepare("INSERT INTO products (farmer_email, product_name, category, price, quantity_kg, image) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssds", $email, $product_name, $category, $price, $quantity_kg, $safe_name);

                    if ($stmt->execute()) {
                        $msg = "✅ Product added successfully!";
                    } else {
                        $msg = "❌ Database error: Could not add product.";
                    }
                    $stmt->close();
                } else {
                    $msg = "❌ Failed to upload image.";
                }
            }
        } else {
            $msg = "❌ Please upload a product image.";
        }
    }
}

// FETCH PRODUCTS
$stmt = $conn->prepare("SELECT * FROM products WHERE farmer_email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Smart Agri Connect | Farmer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

<!-- HEADER -->
<header class="bg-green-700 text-white p-4 flex justify-between items-center shadow z-10 sticky top-0">
    <div class="flex items-center space-x-2">
        <span class="font-bold text-2xl">Smart Agri Connect</span>
        <span class="bg-green-900 px-2 py-1 rounded text-xs ml-2 uppercase">Farmer</span>
    </div>
    <nav class="space-x-5 text-sm font-semibold">
        <a href="#" class="hover:underline">Home</a>
        <a href="#" class="hover:underline">Orders</a>
        <a href="#" class="hover:underline">Support</a>
        <a href="logout.php" class="bg-white text-green-700 px-3 py-1 rounded hover:bg-green-200 transition">Logout</a>
    </nav>
</header>

<div class="max-w-7xl mx-auto w-full pt-6">

    <div class="flex w-full">
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white rounded-xl shadow p-6 mr-7 border-r-2 border-green-100 flex-shrink-0 hidden lg:block">
            <h2 class="text-lg font-bold text-green-700 mb-4">Categories</h2>
            <ul class="space-y-2 text-gray-700 text-sm">
                <?php foreach ($categories as $cat): ?>
                <li><?php echo $cat; ?></li>
                <?php endforeach; ?>
                <li>More...</li>
            </ul>
            <div class="my-6">
                <h3 class="font-semibold text-green-700 mb-2">Our Services</h3>
                <ul class="text-xs text-gray-600 space-y-1">
                    <li>🚚 Free Shipping</li>
                    <li>🎁 Special Offers</li>
                    <li>📞 24/7 Support</li>
                </ul>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1">
            <!-- Banner / Welcome -->
            <div class="rounded-xl overflow-hidden mb-8 flex items-center bg-gradient-to-r from-green-500 to-green-200 p-8 shadow">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">Smart Agri Connect</h1>
                    <p class="text-white mb-4">Welcome, <?php echo htmlspecialchars($farmer['name']); ?>!</p>
                </div>
<div class="ml-auto flex flex-col items-center justify-center hidden md:flex">
    <?php if ($farmer['profile_image'] && file_exists(__DIR__."/uploads/".$farmer['profile_image'])): ?>
        <img src="uploads/<?php echo htmlspecialchars($farmer['profile_image']); ?>"
             alt="Profile"
             class="w-16 h-16 rounded-full object-cover border-2 border-green-700 mb-1" />
    <?php else: ?>
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-bold border-2 border-green-700 mb-1">
            No Image
        </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="flex flex-col items-center space-y-1 mt-1">
        <label class="cursor-pointer text-xs text-green-900 hover:underline">
            <input type="file" name="profile_image" accept="image/*" onchange="this.form.submit()" class="hidden" />
            <?php echo ($farmer['profile_image']) ? "Change" : "Upload"; ?>
        </label>
        <?php if ($farmer['profile_image']): ?>
            <button type="submit" name="remove_profile" class="text-red-700 text-xs hover:underline bg-transparent p-0">Remove</button>
        <?php endif; ?>
        <input type="hidden" name="upload_profile" value="1" />
    </form>
</div>
            </div>

            <!-- Add Product Form -->
            <section id="add-product" class="bg-white rounded-xl shadow p-6 mb-8">
                <h2 class="text-xl font-bold text-green-700 mb-4">Add New Product</h2>
                <form method="POST" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-3">
                    <input type="text" name="product_name" placeholder="Product Name" required
                        class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400" />
                    <select name="category" required class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" step="0.01" name="price" placeholder="Total Price (₹)" required min="0.01"
                        class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400" />
                    <input type="number" step="0.01" name="quantity_kg" placeholder="Available Quantity (kg)" required min="0.01"
                        class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400" />
                    <input type="file" name="image" accept="image/*" required
                        class="border p-2 rounded w-full focus:ring-2 focus:ring-green-400 md:col-span-2" />
                    <button type="submit" name="add_product"
                        class="bg-green-600 text-white py-2 rounded hover:bg-green-700 transition md:col-span-1">Add Product</button>
                </form>
            </section>

            <!-- Products Grid -->
            <section>
                <h2 class="text-xl font-bold text-green-700 mb-4">Your Products</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <?php if ($products->num_rows === 0) { ?>
                        <p class="text-gray-600 col-span-full text-center">You have not added any products yet.</p>
                    <?php } ?>
                    <?php while ($row = $products->fetch_assoc()) { ?>
                        <div class="bg-white p-4 rounded-xl shadow flex flex-col">
                            <span class="inline-block px-2 py-1 text-xs bg-green-100 text-green-700 rounded mb-2">
                                <?php echo htmlspecialchars($row['category']); ?>
                            </span>
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" class="w-full h-32 object-cover rounded-lg mb-4" alt="Product Image">
                            <h3 class="font-bold text-green-700 text-lg mb-1"><?php echo htmlspecialchars($row['product_name']); ?></h3>
                            <p class="text-gray-700 mb-1">Price: ₹<?php echo htmlspecialchars(number_format($row['price'], 2)); ?></p>
                            <p class="text-gray-700 mb-3">Quantity: <?php echo htmlspecialchars(number_format($row['quantity_kg'],2)); ?> kg</p>
                            <div class="mt-auto flex space-x-2">
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="bg-yellow-500 text-white px-3 py-1 rounded w-1/2 text-center">Edit</a>
                                <form action="delete_product.php" method="POST" class="w-1/2" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>" />
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded w-full">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </section>
        </main>
    </div>
</div>

<footer class="bg-gray-900 text-white mt-10 p-4">
    <div class="max-w-6xl mx-auto flex flex-wrap justify-between"></div>
    <div class="text-center text-xs mt-4 opacity-70">&copy; 2025 Smart Agri Connect. All rights reserved.</div>
</footer>
</body>
</html>
