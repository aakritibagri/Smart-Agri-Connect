<?php
include("db.php");
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}
$email = $_SESSION['email'];

// Fetch all orders by this customer, newest first
$orders = [];
$order_query = $conn->prepare("SELECT * FROM orders WHERE customer_email = ? ORDER BY order_time DESC");
$order_query->bind_param("s", $email);
$order_query->execute();
$order_result = $order_query->get_result();
while ($row = $order_result->fetch_assoc()) {
    $orders[] = $row;
}
$order_query->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Order History - Smart Agri Connect</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen">
    <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg px-6 py-8 mt-12">
        <h2 class="text-3xl font-extrabold text-green-800 mb-8 text-center">Your Order History</h2>

        <?php if (empty($orders)): ?>
            <div class="py-10 text-center text-gray-600 text-xl">You have no orders yet.</div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="border rounded-lg shadow-sm p-4 bg-green-50">
                        <div class="flex flex-wrap items-center justify-between mb-2">
                            <span class="font-bold text-lg text-green-700">Order #<?php echo $order['id']; ?></span>
                            <span class="text-sm text-gray-700"><?php echo date("d M Y, h:i A", strtotime($order['order_time'])); ?></span>
                            <span class="font-semibold text-green-900">Total: ₹<?php echo number_format($order['total_price'],2); ?></span>
                        </div>
                        <?php
                        // Fetch items for this order
                        $item_stmt = $conn->prepare("SELECT oi.*, p.product_name, p.image FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id = ?");
                        $item_stmt->bind_param('i', $order['id']);
                        $item_stmt->execute();
                        $items_res = $item_stmt->get_result();
                        ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-green-100 text-green-900 uppercase text-xs">
                                        <th class="py-2 px-2 text-left">Product</th>
                                        <th class="py-2 px-2 text-center">Qty</th>
                                        <th class="py-2 px-2 text-right">Unit Price</th>
                                        <th class="py-2 px-2 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($item = $items_res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="py-2 px-2 flex items-center gap-3">
                                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" class="w-12 h-12 object-cover rounded shadow" />
                                            <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                        </td>
                                        <td class="py-2 px-2 text-center"><?php echo intval($item['quantity']); ?></td>
                                        <td class="py-2 px-2 text-right text-green-700">₹<?php echo number_format($item['price'],2); ?></td>
                                        <td class="py-2 px-2 text-right text-green-950 font-bold">₹<?php echo number_format($item['price'] * $item['quantity'],2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $item_stmt->close(); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
