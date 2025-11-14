<?php
include("db.php");
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}
$email = $_SESSION['email'];

// Fetch cart items with product details
$stmt = $conn->prepare("SELECT c.*, p.product_name, p.image, p.price, p.quantity_kg 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.customer_email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total = 0;
while ($item = $result->fetch_assoc()) {
    $cart_items[] = $item;
    $total += $item['price'] * $item['quantity'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Your Cart - Smart Agri Connect</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen">
    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-lg px-6 py-8 mt-12">
        <h2 class="text-3xl font-extrabold text-green-800 mb-8 text-center">Your Cart</h2>

        <?php if (isset($_SESSION['order_msg'])): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded shadow text-center">
                <?php echo htmlspecialchars($_SESSION['order_msg']); unset($_SESSION['order_msg']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="py-10 text-center text-gray-600 text-xl">Your cart is empty.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-green-100 text-green-900 uppercase text-xs">
                            <th class="py-3 px-2 text-left">Product</th>
                            <th class="py-3 px-2 text-center">Qty</th>
                            <th class="py-3 px-2 text-right">Unit Price</th>
                            <th class="py-3 px-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr class="border-b hover:bg-green-50 transition">
                            <td class="py-4 px-2 flex items-center gap-4">
                                <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" class="w-16 h-16 object-cover rounded shadow" />
                                <div>
                                    <div class="font-bold text-green-800 text-base"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="text-gray-500 text-xs">Available: <?php echo htmlspecialchars($item['quantity_kg']); ?> kg</div>
                                </div>
                            </td>
                            <td class="py-4 px-2 text-center font-semibold"><?php echo intval($item['quantity']); ?></td>
                            <td class="py-4 px-2 text-right text-green-700 font-semibold">₹<?php echo number_format($item['price'],2); ?></td>
                            <td class="py-4 px-2 text-right text-green-950 font-bold">₹<?php echo number_format($item['price'] * $item['quantity'],2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right pt-6 font-bold text-lg text-green-800">Total:</td>
                            <td class="pt-6 text-green-900 font-extrabold text-xl text-right">₹<?php echo number_format($total,2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="flex justify-end mt-8">
                <form action="place_order.php" method="POST">
                    <button type="submit" class="inline-block bg-green-700 text-white px-8 py-3 rounded shadow-lg text-lg font-bold hover:bg-green-800 focus:ring-2 focus:ring-green-500 transition">
                        Place Order
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
