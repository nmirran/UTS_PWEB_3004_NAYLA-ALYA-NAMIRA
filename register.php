<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = "Email sudah terdaftar!";
    } else {
        if ($conn->query("INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')")) {
            header("Location: login.php?success=1");
            exit();
        } else {
            $error = "Gagal melakukan sign up: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body style="background-image: url('assets/images/bg.jpg'); background-size: cover; background-position: center;" class="min-h-screen flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-lg flex w-7/8 max-w-4xl overflow-hidden">
    
    <!-- Left Illustration -->
    <div class="w-1/2 hidden md:flex items-center justify-center bg-pink-100">
      <img src="assets/images/register-ilustration.jpeg" alt="Register Illustration" class="w-full h-full object-cover">
    </div>

    <!-- Form Section -->
    <div class="w-full md:w-1/2 p-8">
      <h2 class="text-3xl font-bold mb-6 text-pink-600">Sign Up</h2>

      <?php if (!empty($error)): ?>
        <p class="text-red-500 mb-4"><?php echo $error; ?></p>
      <?php endif; ?>

      <form id="registerForm" method="POST" class="space-y-4">
        <input type="text" name="username" placeholder="Enter Username" required
          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-300 outline-none">
        <input type="email" name="email" placeholder="Enter Email" required
          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-300 outline-none">
        <input type="password" name="password" placeholder="Enter Password" required
          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-300 outline-none">
        <input type="password" id="confirmPassword" placeholder="Confirm Password" required
          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-300 outline-none">

        <button type="submit"
          class="w-full bg-pink-500 hover:bg-pink-600 text-white font-semibold py-3 rounded-lg transition duration-200">
          Register
        </button>
      </form>

      <p class="text-gray-600 mt-4 text-sm">
        Already have an account?
        <a href="login.php" class="text-pink-500 hover:underline">Sign In</a>
      </p>
    </div>
  </div>

  <script>
    // Validasi password konfirmasi
    $("#registerForm").on("submit", function (e) {
      const pass = $("input[name='password']").val();
      const confirm = $("#confirmPassword").val();
      if (pass !== confirm) {
        e.preventDefault();
        alert("Password dan konfirmasi tidak sama!");
      }
    });
  </script>
</body>
</html>
