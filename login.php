<?php include("db.php"); session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Agri Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-100 flex items-center justify-center min-h-screen">

    <div class="bg-white shadow-lg rounded-xl p-8 w-96">
        <h2 class="text-2xl font-bold text-green-800 text-center mb-4">Login</h2>

        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required
                   class="w-full mb-3 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500">
            <input type="password" name="password" placeholder="Password" required
                   class="w-full mb-3 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500">
            <select name="role" required
                    class="w-full mb-3 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="customer">Customer</option>
                <option value="farmer">Farmer</option>
            </select>

            <button type="submit" name="login"
                    class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">
                Login
            </button>
        </form>
        
        <div class="text-right mt-2">
    <a href="forgot_password.php" class="text-blue-600 hover:underline">Forgot Password?</a>
        </div>


        <p class="text-center text-sm mt-4">
            New here?
            <a href="signup_customer.php" class="text-green-700 font-semibold">Customer Signup</a> |
            <a href="signup_farmer.php" class="text-green-700 font-semibold">Farmer Signup</a>
        </p>

        <?php
        if (isset($_POST['login'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];
            $role = $_POST['role'];

            $table = ($role == "customer") ? "customers" : "farmers";
            $sql = "SELECT * FROM $table WHERE email='$email'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $role;

                    if ($role == "customer") {
                        header("Location: dashboard_customer.php");
                    } else {
                        header("Location: dashboard_farmer.php");
                    }
                    exit();
                } else {
                    echo "<p class='text-red-600 text-center mt-3'>Invalid password!</p>";
                }
            } else {
                echo "<p class='text-red-600 text-center mt-3'>No user found!</p>";
            }
        }
        ?>
    </div>
</body>
</html>
