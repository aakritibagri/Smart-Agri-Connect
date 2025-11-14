<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Agri Connect - Splash</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-100 flex items-center justify-center h-screen">

    <div class="text-center">
        <img src="splash.png" alt="Smart Agri Connect Logo" class="w-40 mx-auto animate-bounce">
        <h1 class="text-3xl font-bold text-green-800 mt-4 animate-pulse">
            Smart Agri Connect
        </h1>
        <p class="text-green-700 mt-2">Connecting Farmers and Customers</p>
    </div>

    <script>
        setTimeout(() => {
            window.location.href = "login.php";
        }, 4000);
    </script>

</body>
</html>