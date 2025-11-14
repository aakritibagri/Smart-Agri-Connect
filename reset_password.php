<?php
include("db.php");
session_start();

$msg = "";
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $token = $_POST['token'];
    $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT expires_at FROM password_resets WHERE email=? AND token=?");
    $stmt->bind_param('ss', $email, $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (strtotime($row['expires_at']) > time()) {
            $stmt2 = $conn->prepare("UPDATE customers SET password=? WHERE email=?");
            $stmt2->bind_param('ss', $new_pass, $email);
            $stmt2->execute();
            $conn->query("DELETE FROM password_resets WHERE email='$email'");
            $msg = "Password changed. <a href='login.php' class='text-green-700 underline'>Login now</a>";
        } else {
            $msg = "This reset link has expired.";
        }
    } else {
        $msg = "Invalid or expired reset link.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-green-50">
    <div class="bg-white shadow-lg rounded-xl p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4 text-green-800 text-center">Reset Password</h2>
        <?php if ($msg): ?><div class="mb-3 text-center text-sm text-green-700"><?php echo $msg; ?></div><?php endif; ?>
        <?php if (!$msg): ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" required name="password" class="w-full border rounded px-3 py-2" placeholder="New password" />
            <button type="submit" class="w-full bg-green-700 text-white py-2 rounded hover:bg-green-800">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
