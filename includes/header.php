<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_name = $_SESSION['username'] ?? 'User';
$user_email = $_SESSION['email'] ?? 'user@example.com';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>To-Do App</title>
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside class="w-64 bg-red-500 text-white p-6 flex flex-col justify-between fixed h-full">
    <div>
      <div class="flex flex-col items-center mb-8">
        <img src="https://via.placeholder.com/70" class="rounded-full mb-2" />
        <h2 class="text-lg font-semibold"><?php echo htmlspecialchars($user_name); ?></h2>
        <p class="text-sm"><?php echo htmlspecialchars($user_email); ?></p>
      </div>

      <nav class="space-y-3">
        <a href="dashboard.php" class="block py-2 px-4 rounded hover:bg-red-600">🏠 Dashboard</a>
        <a href="tasks.php" class="block py-2 px-4 rounded hover:bg-red-600">🗒️ My Tasks</a>
        <a href="categories.php" class="block py-2 px-4 rounded hover:bg-red-600">📂 Task Categories</a>
        <a href="account.php" class="block py-2 px-4 rounded hover:bg-red-600">⚙️ Account</a>
      </nav>
    </div>
    <a href="logout.php" class="block mt-6 py-2 px-4 bg-white text-red-600 rounded hover:bg-gray-200 text-center font-semibold">
      Logout
    </a>
  </aside>

  <!-- Main Content -->
  <main class="ml-64 flex-1 p-8">
