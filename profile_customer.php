<?php
include("db.php");
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}
$email = $_SESSION['email'];

// Handle profile image upload/remove
if (isset($_POST['upload_profile'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $img_tmp = $_FILES['profile_image']['tmp_name'];
        $img_name = $_FILES['profile_image']['name'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $img_tmp);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            $msg = "❌ Only JPG, PNG, GIF allowed.";
        } else {
            $safe_name = uniqid() . '-' . preg_replace("/[^a-zA-Z0-9\.\-\_]/", "", basename($img_name));
            $path = __DIR__ . "/uploads/" . $safe_name;
            if (move_uploaded_file($img_tmp, $path)) {
                // Remove old image if exists
                $stmt_old = $conn->prepare("SELECT profile_image FROM customers WHERE email=?");
                $stmt_old->bind_param("s", $email);
                $stmt_old->execute();
                $stmt_old->bind_result($old_img);
                $stmt_old->fetch();
                $stmt_old->close();
                if ($old_img && file_exists(__DIR__ . "/uploads/" . $old_img)) unlink(__DIR__ . "/uploads/" . $old_img);

                $stmt = $conn->prepare("UPDATE customers SET profile_image=? WHERE email=?");
                $stmt->bind_param("ss", $safe_name, $email);
                $stmt->execute();
                $stmt->close();
                $msg = "✅ Profile image updated!";
            } else {
                $msg = "❌ Upload failed!";
            }
        }
    }
}
if (isset($_POST['remove_profile'])) {
    $stmt_old = $conn->prepare("SELECT profile_image FROM customers WHERE email=?");
    $stmt_old->bind_param("s", $email);
    $stmt_old->execute();
    $stmt_old->bind_result($old_img);
    $stmt_old->fetch();
    $stmt_old->close();
    if ($old_img && file_exists(__DIR__ . "/uploads/" . $old_img)) unlink(__DIR__ . "/uploads/" . $old_img);
    $stmt = $conn->prepare("UPDATE customers SET profile_image=NULL WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
    $msg = "✅ Profile image removed!";
}

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    $feedback_text = trim($_POST['feedback_text']);
    if (!empty($feedback_text)) {
        $stmt = $conn->prepare("INSERT INTO feedback (customer_email, feedback_text) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $feedback_text);
        if ($stmt->execute()) {
            $feedback_msg = "✅ Feedback submitted successfully!";
        } else {
            $feedback_msg = "❌ Failed to submit feedback.";
        }
        $stmt->close();
    } else {
        $feedback_msg = "❌ Feedback cannot be empty.";
    }
}

// Fetch customer details (with image)
$stmt = $conn->prepare("SELECT name, email, profile_image FROM customers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile - Smart Agri Connect</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen">
    <div class="max-w-xl mx-auto bg-white rounded-xl shadow-xl px-6 py-8 mt-16">
        <h2 class="text-3xl font-extrabold text-green-800 mb-8 text-center">Your Profile</h2>
        <div class="flex flex-col items-center gap-6">
            <div>
                <?php if ($customer['profile_image'] && file_exists(__DIR__ . "/uploads/" . $customer['profile_image'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($customer['profile_image']); ?>"
                         alt="Profile Image"
                         class="w-32 h-32 rounded-full object-cover border-4 border-green-700 shadow" />
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-2xl font-bold border-4 border-green-700 shadow">
                        No Image
                    </div>
                <?php endif; ?>
            </div>
            <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 items-center">
                <input type="file" name="profile_image" accept="image/*" class="rounded border px-2 py-1" />
                <button type="submit" name="upload_profile" class="bg-green-600 text-white py-1 px-4 rounded hover:bg-green-700 transition">Upload/Change</button>
                <?php if ($customer['profile_image']): ?>
                    <button type="submit" name="remove_profile" class="bg-red-500 text-white py-1 px-4 rounded hover:bg-red-600">Remove</button>
                <?php endif; ?>
            </form>
            <?php if (!empty($msg)) echo "<p class='text-green-700 mt-2'>" . htmlspecialchars($msg) . "</p>"; ?>
        </div>

        <div class="mt-8 space-y-3">
            <div class="flex items-center gap-2 text-lg">
                <span class="font-semibold text-green-800">Name:</span>
                <span class="text-gray-800"><?php echo htmlspecialchars($customer['name']); ?></span>
            </div>
            <div class="flex items-center gap-2 text-lg">
                <span class="font-semibold text-green-800">Email:</span>
                <span class="text-gray-800"><?php echo htmlspecialchars($customer['email']); ?></span>
            </div>
        </div>

        <div class="mt-8">
            <h3 class="text-xl font-bold text-green-800 mb-4">Leave Feedback</h3>
            <form method="POST" class="space-y-4">
                <textarea name="feedback_text" rows="4" placeholder="Write your feedback here..." class="w-full border rounded px-3 py-2" required></textarea>
                <button type="submit" name="submit_feedback" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700">Submit Feedback</button>
            </form>
            <?php if (!empty($feedback_msg)) echo "<p class='text-green-700 mt-2'>" . htmlspecialchars($feedback_msg) . "</p>"; ?>
        </div>

        <div class="text-center mt-8">
            <a href="dashboard_customer.php" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
