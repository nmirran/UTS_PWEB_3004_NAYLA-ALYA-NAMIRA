<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

if (isset($_POST['add_task'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $subcategory_id = intval($_POST['subcategory_id'] ?? 0);
    $status = $_POST['status'] ?? 'Not Started';

    if ($title !== '') {
        $stmt = $conn->prepare("
            INSERT INTO tasks (user_id, category_id, subcategory_id, title, description, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiisss", $user_id, $category_id, $subcategory_id, $title, $desc, $status);
        if ($stmt->execute()) {
            $message = "Task '$title' berhasil ditambahkan!";
            header("Location: tasks.php");
            exit();
        } else {
            $message = "Gagal menambahkan task: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Judul task tidak boleh kosong!";
    }
}

$categories = [];
$cat_query = $conn->prepare("SELECT id, name FROM categories WHERE user_id = ?");
$cat_query->bind_param("i", $user_id);
$cat_query->execute();
$cat_res = $cat_query->get_result();

while ($row = $cat_res->fetch_assoc()) {
    $categories[$row['id']] = $row['name'];
}
$cat_query->close();

$subcategories = [];
$subcat_query = $conn->prepare("SELECT s.id, s.name, s.category_id
    FROM subcategories s
    JOIN categories c ON s.category_id = c.id
    WHERE c.user_id = ?");
$subcat_query->bind_param("i", $user_id);
$subcat_query->execute();
$subcat_res = $subcat_query->get_result();

while ($row = $subcat_res->fetch_assoc()) {
    $subcategories[$row['id']] = $row;
}
$subcat_query->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9fafb;
        }

        /* Smooth fade-in for popup */
        .fade-in {
            display: none;
        }
    </style>
</head>
<body class="flex items-center justify-center h-screen bg-black/40">

<div id="taskModal" class="fade-in bg-white rounded-2xl shadow-2xl w-[600px] p-8 relative">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-[#e83e8c]">Add New Task</h2>
        <a href="tasks.php" class="text-gray-600 hover:text-[#e83e8c] text-sm font-medium transition">Go Back</a>
    </div>

    <form method="POST" action="">
        <div class="mb-4">
            <label class="block font-medium mb-2">Title</label>
            <input type="text" name="title" placeholder="Enter task title" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#e83e8c] outline-none transition">
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-2">Task Description</label>
            <textarea name="description" placeholder="Start writing here..."
                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#e83e8c] outline-none transition resize-y"></textarea>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-2">Category</label>
            <select name="category_id" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#e83e8c] outline-none transition">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $id => $name): ?>
                    <option value="<?= $id; ?>"><?= htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-2">Subcategory</label>
            <select name="subcategory_id"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#e83e8c] outline-none transition">
                <option value="">-- Select Subcategory --</option>
                <?php foreach ($subcategories as $sid => $sub): ?>
                    <option value="<?= $sid; ?>"><?= htmlspecialchars($sub['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-6">
            <label class="block font-medium mb-2">Status</label>
            <select name="status"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#e83e8c] outline-none transition">
                <option value="Not Started">Not Started</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
            </select>
        </div>

        <button type="submit" name="add_task"
                class="w-full bg-[#e83e8c] text-white font-semibold py-3 rounded-lg hover:bg-[#d63384] transition">
            Done
        </button>
    </form>

    <?php if ($message): ?>
        <p class="text-center text-green-600 mt-4 font-medium"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function () {
        $("#taskModal").fadeIn(300);
    });
</script>

</body>
</html>