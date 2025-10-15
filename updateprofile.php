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

if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username'] ?? '');

    if ($new_username !== '') {
        $update_stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_username, $user_id);
        if ($update_stmt->execute()) {
            $_SESSION['username'] = $new_username;
            $message = "Profil berhasil diperbarui!";
            header("Location: accountinfo.php");
            exit();
        } else {
            $message = "Gagal memperbarui profil: " . $conn->error;
        }
        $update_stmt->close();
    } else {
        $message = "Username tidak boleh kosong!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fdfdfd;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 40px;
            width: 400px;
            text-align: center;
        }

        h2 {
            color: #e83e8c;
            margin-bottom: 20px;
        }

        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            outline: none;
            font-size: 15px;
            transition: 0.3s;
        }

        input[type="text"]:focus {
            border-color: #e83e8c;
            box-shadow: 0 0 5px rgba(232,62,140,0.3);
        }

        input[disabled] {
            background-color: #f8f8f8;
            color: #888;
            cursor: not-allowed;
        }

        button {
            background-color: #e83e8c;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
        }

        button:hover {
            background-color: #d63384;
        }

        .message {
            margin-top: 15px;
            color: #28a745;
            font-weight: 500;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: #e83e8c;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Update Profile</h2>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Enter Username" value="">
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        <button type="submit" name="update_profile">Submit</button>
    </form>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <a href="accountinfo.php" class="back-link">Back to Account Info</a>
</div>

</body>
</html>
