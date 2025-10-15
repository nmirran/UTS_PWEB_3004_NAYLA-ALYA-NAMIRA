<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $task_id = intval($_GET['id']);

    // Hapus hanya jika task milik user tersebut
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);

    if ($stmt->execute()) {
        header("Location: tasks.php?msg=deleted");
        exit();
    } else {
        echo "Gagal menghapus task: " . $conn->error;
    }

    $stmt->close();
} else {
    header("Location: tasks.php");
    exit();
}
?>
