<?php
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $check = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $user = $check->fetch_assoc(); 

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Akun belum terdaftar!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body style="background-image: url('assets/images/bg.jpg'); background-size: cover; background-position: center;" class="min-h-screen flex items-center justify-center">

  <div class="bg-white rounded-2xl shadow-lg flex w-3/4 max-w-4xl overflow-hidden">
    
    <!-- FORM SECTION (kiri) -->
    <div class="w-full md:w-1/2 p-8 flex flex-col justify-center">
      <h2 class="text-3xl font-bold mb-6 text-pink-600">Sign In</h2>

      <?php if (!empty($error)): ?>
        <p class="text-red-500 mb-4"><?php echo $error; ?></p>
      <?php endif; ?>

      <?php if (isset($_GET['success'])): ?>
        <p class="text-green-600 mb-4">Registrasi berhasil, silakan login!</p>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <input type="email" name="email" placeholder="Enter Email" required
          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-300 outline-none">
        <input type="password" name="password" placeholder="Enter Password" required
          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-300 outline-none">

        <button type="submit"
          class="w-full bg-pink-500 hover:bg-pink-600 text-white font-semibold py-3 rounded-lg transition duration-200">
          Login
        </button>
      </form>

      <p class="text-gray-600 mt-4 text-sm">
        Don’t have an account?
        <a href="register.php" class="text-pink-500 hover:underline">Create One</a>
      </p>
    </div>

    <!-- ILLUSTRATION (kanan) -->
    <div class="w-1/2 hidden md:flex items-center justify-center bg-pink-100">
      <img src="assets/images/login.jpg" alt="Login Illustration" class="w-full h-full object-cover">
    </div>

  </div>

</body>
</html>
