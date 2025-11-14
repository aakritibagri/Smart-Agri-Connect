<?php
include("db.php");
session_start();

// Check customer login and role
if (!isset($_SESSION['email']) || $_SESSION['role'] != "customer") {
    header("Location: login.php");
    exit();
}
$email = $_SESSION['email'];

// Fetch customer name for profile display
$profile_query = $conn->query("SELECT name FROM customers WHERE email='$email'");
$profile_data = $profile_query->fetch_assoc();
$full_name = $profile_data ? $profile_data['name'] : $email;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Customer Dashboard - Smart Agri Connect</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">

<!-- HEADER -->
<header class="bg-green-700 text-white shadow-md sticky top-0 z-30">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between px-6 py-4">
        <h1 class="text-3xl font-bold text-white">Smart Agri Connect</h1>
        <nav class="flex items-center space-x-6 text-sm font-semibold">
            <a href="dashboard_customer.php" class="hover:underline focus:underline">Home</a>
            <a href="cart.php" class="hover:underline focus:underline">Place Order</a>
            <a href="orders.php" class="hover:underline focus:underline">Order History</a>
            <a href="profile_customer.php" class="hover:underline focus:underline">Profile</a>
            <a href="logout.php" class="hover:underline focus:underline">Logout</a>
        </nav>
    </div>
</header>

<main class="flex-grow max-w-7xl mx-auto px-6 py-8">
    <section class="mb-10">
        <h2 class="text-2xl font-semibold text-green-800 mb-2">Welcome, <?php echo htmlspecialchars($full_name); ?>!</h2>
        <p class="text-gray-700">Explore fresh products from farmers near you.</p>
    </section>

    <!-- Search Bar -->
    <section class="mb-8">
        <input id="search_products" type="search" placeholder="Search products..." 
            class="w-full max-w-md p-3 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-green-400" />
    </section>

    <!-- Filter Categories -->
    <section class="mb-8">
        <h3 class="text-lg font-semibold mb-2">Filter by Category</h3>
        <div id="category_filters" class="flex flex-wrap gap-3">
            <?php 
            $categories = ["Fruits", "Vegetables", "Foodgrains", "Oils & Ghee", "Other"];
            foreach ($categories as $cat) { ?>
                <label class="inline-flex items-center cursor-pointer space-x-2 bg-white shadow rounded px-4 py-2 text-gray-700 hover:bg-green-100">
                    <input type="checkbox" class="category-checkbox" value="<?php echo $cat; ?>" />
                    <span><?php echo $cat; ?></span>
                </label>
            <?php } ?>
        </div>
    </section>

    <!-- Product Grid -->
    <section>
        <h3 class="text-xl font-bold text-green-800 mb-6">Available Products</h3>
        <div id="products_container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6"></div>
    </section>
</main>

<footer class="bg-gray-900 text-white p-4 mt-16">
    <div class="max-w-7xl mx-auto text-center text-xs opacity-70">
        &copy; 2025 Smart Agri Connect. All rights reserved.
    </div>
</footer>

<script>
// Fetch all products via AJAX on page load
async function fetchProducts() {
    const response = await fetch('fetch_products.php');
    const products = await response.json();
    return products;
}

function renderProducts(products) {
    const container = document.getElementById('products_container');
    container.innerHTML = '';

    if (products.length === 0) {
        container.innerHTML = '<p class="col-span-full text-gray-600 text-center">No products available.</p>';
        return;
    }
    for (const p of products) {
        container.innerHTML += `
            <div class="bg-white shadow rounded-xl p-4 flex flex-col">
                <img src="uploads/${p.image}" alt="${p.product_name}" class="w-full h-40 object-cover rounded mb-4" />
                <h4 class="font-bold text-green-700 text-lg">${p.product_name}</h4>
                <p class="text-sm text-gray-600">Category: ${p.category}</p>
                <p class="text-sm text-gray-700">Farmer: ${p.farmer_name}</p>
                <p class="text-green-800 mt-1 font-semibold">₹${parseFloat(p.price).toFixed(2)} for ${p.quantity_kg} kg</p>
                <div class="mt-auto flex gap-2 pt-4">
                    <form action="add_to_cart.php" method="POST" class="w-full">
                        <input type="hidden" name="product_id" value="${p.id}" />
                        <input type="hidden" name="quantity" value="1" />
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition w-full text-center font-semibold">
                            Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        `;
    }
}

function filterProducts(products, searchTerm, selectedCategories) {
    return products.filter(p => {
        const matchesSearch = p.product_name.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesCategory = selectedCategories.length === 0 || selectedCategories.includes(p.category);
        return matchesSearch && matchesCategory;
    });
}

window.addEventListener('DOMContentLoaded', async () => {
    const products = await fetchProducts();

    const searchInput = document.getElementById('search_products');
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');

    function updateView() {
        const searchTerm = searchInput.value.trim();
        const selectedCategories = Array.from(categoryCheckboxes)
                                      .filter(chk => chk.checked)
                                      .map(chk => chk.value);

        const filtered = filterProducts(products, searchTerm, selectedCategories);
        renderProducts(filtered);
    }

    searchInput.addEventListener('input', updateView);
    categoryCheckboxes.forEach(chk => chk.addEventListener('change', updateView));

    // Initial render
    renderProducts(products);
});
</script>

</body>
<?php if (isset($_SESSION['add_cart_message'])): ?>
    <div id="cart-notification" class="fixed top-6 right-6 bg-green-600 text-white px-5 py-3 rounded shadow-lg z-50 animate-pulse">
        <?php 
        echo htmlspecialchars($_SESSION['add_cart_message']);
        unset($_SESSION['add_cart_message']); 
        ?>
    </div>
    <script>
    // Hide notification after 2 seconds
    setTimeout(() => {
        const notif = document.getElementById('cart-notification');
        if (notif) notif.style.display = 'none';
    }, 2000);
    </script>
<?php endif; ?>

</html>
