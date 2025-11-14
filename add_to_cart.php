<?php
include("db.php");
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

$customer_email = $_SESSION['email'];

if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    header("Location: dashboard_customer.php");
    exit();
}

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);
if ($quantity <= 0) $quantity = 1;

// Check if product already in cart
$stmt = $conn->prepare("SELECT quantity FROM cart WHERE customer_email = ? AND product_id = ?");
$stmt->bind_param("si", $customer_email, $product_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($existing_qty);
    $stmt->fetch();
    $new_qty = $existing_qty + $quantity;
    $stmt->close();

    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE customer_email = ? AND product_id = ?");
    $update_stmt->bind_param("isi", $new_qty, $customer_email, $product_id);
    $update_stmt->execute();
    $update_stmt->close();
} else {
    $stmt->close();
    $insert_stmt = $conn->prepare("INSERT INTO cart (customer_email, product_id, quantity) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("sii", $customer_email, $product_id, $quantity);
    $insert_stmt->execute();
    $insert_stmt->close();
}

// Set a flash message so the customer sees confirmation after redirect
$_SESSION['add_cart_message'] = "Product added to cart successfully!";

header("Location: dashboard_customer.php");
exit();
?>
