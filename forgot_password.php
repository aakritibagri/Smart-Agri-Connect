<?php
include("db.php");
session_start();

$msg = "";

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare("SELECT email FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour validity
        $conn->query("DELETE FROM password_resets WHERE email='$email'");
        $stmt2 = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt2->bind_param('sss', $email, $token, $expires);
        $stmt2->execute();
        $reset_link = "http://yourdomain.com/reset_password.php?email=$email&token=$token";
        mail($email, "Password Reset", "Click this link to reset your password: $reset_link");
        $msg = "A password reset link has been sent to your email.";
    } else {
        $msg = "No account found with this email.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-green-50">
    <div class="bg-white shadow-lg rounded-xl p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4 text-green-800 text-center">Forgot Password</h2>
        <?php if ($msg): ?><div class="mb-3 text-center text-sm text-green-700"><?php echo $msg; ?></div><?php endif; ?>
        <form method="POST" class="space-y-4">
            <input type="email" name="email" required class="w-full border rounded px-3 py-2" placeholder="Your registered email" />
            <button type="submit" name="submit" class="w-full bg-green-700 text-white py-2 rounded hover:bg-green-800">Send Reset Link</button>
        </form>
        <div class="text-center mt-4">
            <a href="login.php" class="text-sm text-blue-600 hover:underline">Back to Login</a>
        </div>
    </div>
</body>
</html>
