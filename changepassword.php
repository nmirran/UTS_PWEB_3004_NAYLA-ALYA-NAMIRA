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

if (isset($_POST['change_password'])) {
    $old_pass = trim($_POST['old_password'] ?? '');
    $new_pass = trim($_POST['new_password'] ?? '');
    $confirm_pass = trim($_POST['confirm_password'] ?? '');

    if ($old_pass === '' || $new_pass === '' || $confirm_pass === '') {
        $message = "Semua kolom wajib diisi!";
    } elseif ($new_pass !== $confirm_pass) {
        $message = "Konfirmasi password tidak cocok!";
    } else {
        $check_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_stmt->bind_result($hashed_pass);
        $check_stmt->fetch();
        $check_stmt->close();

        if (!password_verify($old_pass, $hashed_pass)) {
            $message = "Password lama salah!";
        } else {
            $new_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hashed, $user_id);
            if ($update_stmt->execute()) {
                $message = "Password berhasil diubah!";
                header("Location: accountinfo.php");
                exit();
            } else {
                $message = "Gagal mengubah password: " . $conn->error;
            }
            $update_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
    body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="flex items-center justify-center h-screen bg-black/40">

<div id="changePasswordCard" class="fade-in bg-white rounded-2xl shadow-2xl w-[500px] p-8 relative">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-[#e83e8c]">Change Password</h2>
        <a href="accountinfo.php" class="text-gray-600 hover:text-[#e83e8c] text-sm font-medium transition">Go Back</a>
    </div>

    <form method="POST" action="">
        <div class="mb-5">
            <label class="block font-medium mb-2">Password Lama</label>
            <input type="password" name="old_password" placeholder="Masukkan password lama" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#e83e8c] outline-none transition">
        </div>

        <div class="mb-5">
            <label class="block font-medium mb-2">Password Baru</label>
            <input type="password" name="new_password" placeholder="Masukkan password baru" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#e83e8c] outline-none transition">
        </div>

        <div class="mb-6">
            <label class="block font-medium mb-2">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" placeholder="Ketik ulang password baru" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#e83e8c] outline-none transition">
        </div>

        <button type="submit" name="change_password"
                class="w-full bg-[#e83e8c] text-white font-semibold py-3 rounded-lg hover:bg-[#d63384] transition">
            Ubah Password
        </button>
    </form>

    <?php if ($message): ?>
        <p class="text-center mt-5 font-medium text-<?php echo (strpos($message, 'berhasil') !== false) ? 'green-600' : 'red-500'; ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function () {
        $("#changePasswordCard").fadeIn(300);
    });
</script>

</body>
</html>