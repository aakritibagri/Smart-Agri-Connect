<?php
include("db.php");

// Query to get products with farmer name
$query = "SELECT p.id, p.product_name, p.category, p.price, p.quantity_kg, p.image, f.name AS farmer_name 
          FROM products p JOIN farmers f ON p.farmer_email = f.email 
          WHERE p.quantity_kg > 0 ORDER BY p.id DESC";

$result = $conn->query($query);

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

header('Content-Type: application/json');
echo json_encode($products);
?>
