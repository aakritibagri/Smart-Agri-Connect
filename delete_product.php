<?php
session_start();
include("db.php");

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $email = $_SESSION['email'];

    // Verify product ownership before deleting
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND farmer_email = ?");
    $stmt->bind_param("is", $product_id, $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($image_name);
    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        $stmt->close();

        // Delete product record
        $del_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $del_stmt->bind_param("i", $product_id);
        if ($del_stmt->execute()) {
            // Delete image file if exists
            $image_path = __DIR__ . "/uploads/" . $image_name;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            $_SESSION['msg'] = "✅ Product deleted successfully!";
        } else {
            $_SESSION['msg'] = "❌ Failed to delete product.";
        }
        $del_stmt->close();
    } else {
        $_SESSION['msg'] = "❌ Product not found or you don’t have permission to delete it.";
        $stmt->close();
    }
}

header("Location: dashboard_farmer.php");
exit();
