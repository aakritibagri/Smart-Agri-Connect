<?php
include("db.php");
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}
$email = $_SESSION['email'];

// Get all cart items
$stmt = $conn->prepare("SELECT c.*, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.customer_email = ?");
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

if (empty($cart_items)) {
    $_SESSION['order_msg'] = "Your cart is empty!";
    header("Location: cart.php");
    exit();
}

// Create new order
$order_stmt = $conn->prepare("INSERT INTO orders (customer_email, total_price) VALUES (?, ?)");
$order_stmt->bind_param("sd", $email, $total);
$order_stmt->execute();
$order_id = $conn->insert_id;
$order_stmt->close();

// Add items to order_items
$item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($cart_items as $item) {
    $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
    $item_stmt->execute();
}
$item_stmt->close();

// Clear cart
$del_stmt = $conn->prepare("DELETE FROM cart WHERE customer_email = ?");
$del_stmt->bind_param("s", $email);
$del_stmt->execute();
$del_stmt->close();

$_SESSION['order_msg'] = "Your order has been placed successfully!";
header("Location: cart.php");
exit();
?>
