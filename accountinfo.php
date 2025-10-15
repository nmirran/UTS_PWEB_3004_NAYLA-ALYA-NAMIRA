<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Account Info</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-[#F2A2A2] text-gray-800 flex flex-col justify-between shadow-lg">
        <div>
            <div class="text-center py-6 border-b border-pink-300/40">
                <div class="h-16 w-16 mx-auto bg-white rounded-full shadow-md overflow-hidden">
                    <img src="assets/images/user.jpg" class="h-16 w-16 rounded-full object-cover" alt="User Avatar">
                </div>
                <h2 class="mt-3 font-semibold text-lg"><?php echo htmlspecialchars($user['username']); ?></h2>
                <p class="text-sm text-gray-700"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <nav class="mt-6 space-y-2">
                <a href="dashboard.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">Dashboard</a>
                <a href="tasks.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">My Task</a>
                <a href="categories.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">Task Categories</a>
                <a href="accountinfo.php" class="flex items-center px-6 py-2 rounded-md bg-white text-gray-900 font-semibold shadow-sm">Account Info</a>
            </nav>
        </div>
        <a href="logout.php" class="flex items-center px-6 py-4 hover:bg-[#e87474] transition font-semibold border-t border-pink-300/40">Logout</a>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-10">
        <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-xl p-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 border-b-2 border-[#F2A2A2] inline-block pb-1">
                    Account Information
                </h1>
                <a href="dashboard.php" class="text-sm text-gray-500 hover:text-[#f48c8c] font-semibold">Go Back</a>
            </div>

            <!-- Profile Header -->
            <div class="flex items-center gap-4 mb-6">
                <div class="h-16 w-16 rounded-full overflow-hidden shadow-md">
                    <img src="assets/images/user.jpg" class="h-16 w-16 object-cover" alt="User Avatar">
                </div>
                <div>
                    <h2 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>

            <!-- Info List -->
            <div class="space-y-3 mb-8">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Created At:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4">
                <a href="updateprofile.php"
                   class="flex-1 text-center bg-[#F2A2A2] hover:bg-[#f48c8c] text-white font-medium py-2 rounded-lg transition">
                    Update Profile
                </a>
                <a href="changepassword.php"
                   class="flex-1 text-center bg-[#f48c8c] hover:bg-[#F2A2A2] text-white font-medium py-2 rounded-lg transition">
                    Change Password
                </a>
            </div>
        </div>
    </main>
</div>

</body>
</html>

