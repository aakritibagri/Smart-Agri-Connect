<?php include("db.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Signup - Smart Agri Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-lg rounded-xl p-8 w-96">
        <h2 class="text-2xl font-bold text-green-800 text-center mb-4">Customer Signup</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required class="w-full mb-3 p-2 border rounded">
            <input type="email" name="email" placeholder="Email" required class="w-full mb-3 p-2 border rounded">
            <input type="password" name="password" placeholder="Password" required class="w-full mb-3 p-2 border rounded">
            <button type="submit" name="signup" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                Signup
            </button>
        </form>

        <?php
        if (isset($_POST['signup'])) {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $sql = "INSERT INTO customers (name, email, password) VALUES ('$name', '$email', '$password')";
            if ($conn->query($sql) === TRUE) {
                echo "<p class='text-green-700 text-center mt-3'>Signup successful! <a href='login.php'>Login now</a></p>";
            } else {
                echo "<p class='text-red-600 text-center mt-3'>Error: " . $conn->error . "</p>";
            }
        }
        ?>
    </div>
</body>
</html>
