<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

if (!isset($_GET['id'])) {
    header("Location: tasks.php");
    exit();
}

$task_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    die("Task tidak ditemukan atau bukan milikmu!");
}

if (isset($_POST['update_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $category_id = $_POST['category_id'] ?? null;
    $subcategory_id = $_POST['subcategory_id'] ?? null;

    $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, STATUS=?, category_id=?, subcategory_id=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sssiiii", $title, $description, $status, $category_id, $subcategory_id, $task_id, $user_id);

    if ($stmt->execute()) {
        $message = "Task berhasil diperbarui!";
    } else {
        $message = "Gagal memperbarui task: " . $conn->error;
    }

    $stmt->close();

    header("Location: tasks.php");
    exit();
}

$cat_res = $conn->query("SELECT * FROM categories WHERE user_id = $user_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Task</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded-2xl shadow-md">
        <h1 class="text-2xl font-bold mb-4">✏️ Edit Task</h1>

        <?php if ($message): ?>
            <div class="p-3 mb-3 text-green-700 bg-green-100 rounded-lg">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label class="block mb-2 font-semibold">Judul Task</label>
            <input type="text" name="title"
                value="<?php echo htmlspecialchars($task['title'] ?? ''); ?>"
                required class="w-full border rounded-lg px-3 py-2 mb-4">

            <label class="block mb-2 font-semibold">Deskripsi</label>
            <textarea name="description" rows="4" required
                class="w-full border rounded-lg px-3 py-2 mb-4"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>

            <label class="block mb-2 font-semibold">Status</label>
            <select name="status" class="w-full border rounded-lg px-3 py-2 mb-4">
                <option value="Not Started" <?php echo ($task['status'] ?? '') == 'Not Started' ? 'selected' : ''; ?>>Not Started</option>
                <option value="In Progress" <?php echo ($task['status'] ?? '') == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="Completed" <?php echo ($task['status'] ?? '') == 'Completed' ? 'selected' : ''; ?>>Completed</option>
            </select>


            <label class="block mb-2 font-semibold">Kategori</label>
            <select name="category_id" class="w-full border rounded-lg px-3 py-2 mb-4">
                <option value="">--Pilih Kategori--</option>
                <?php while ($c = $cat_res->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>"
                        <?php echo ($c['id'] == ($task['category_id'] ?? null)) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label class="block mb-2 font-semibold">Subkategori</label>
            <select name="subcategory_id" id="subcategory" class="w-full border rounded-lg px-3 py-2 mb-4">
                <option value="">--Pilih Subkategori--</option>
            <?php
            // ambil subkategori yang sesuai user (bisa filter sesuai kategori)
            $sub_res = $conn->query("
                SELECT s.id, s.name, s.category_id 
                FROM subcategories s 
                JOIN categories c ON s.category_id = c.id 
                WHERE c.user_id = $user_id
            ");
            while ($s = $sub_res->fetch_assoc()): ?>
                <option value="<?php echo $s['id']; ?>"
                    data-category="<?php echo $s['category_id']; ?>"
                    <?php echo ($s['id'] == ($task['subcategory_id'] ?? null)) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

            <button type="submit" name="update_task"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Simpan Perubahan</button>
            <a href="tasks.php" class="ml-2 text-gray-600 hover:underline">Batal</a>
        </form>

    </div>
</body>
</html>
