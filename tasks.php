<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

$user_query = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_res = $user_query->get_result();
$user = $user_res->fetch_assoc();
$user_query->close();

if (isset($_POST['update_status'])) {
    $task_id = intval($_POST['task_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';

    if ($task_id > 0 && in_array($new_status, ['Not Started', 'In Progress', 'Completed'])) {
        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $new_status, $task_id, $user_id);
        if ($stmt->execute()) {
            $message = "Status task berhasil diperbarui!";
        } else {
            $message = "Gagal memperbarui status: ". $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Data update tidak valid!";
    }
}

$categories = [];
$cat_query = $conn->prepare("SELECT * FROM categories WHERE user_id = ?");
$cat_query->bind_param("i", $user_id);
$cat_query->execute();
$cat_res = $cat_query->get_result();
while ($cat = $cat_res->fetch_assoc()) {
    $categories[$cat['id']] = $cat['name'];
}
$cat_query->close();

$subcategories = [];
$sub_res = $conn->query("SELECT s.id, s.name, s.category_id FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE c.user_id = $user_id");
while ($sub = $sub_res->fetch_assoc()) {
    $subcategories[$sub['id']] = [
        'name' => $sub['name'],
        'category_id' => $sub['category_id']
    ];
}

$sql = "
    SELECT t.id, t.title, t.description, t.status, 
           c.name AS category_name, s.name AS subcategory_name
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN subcategories s ON t.subcategory_id = s.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$tasks = [];
while ($row = $res->fetch_assoc()) {
    $tasks[] = $row;
}
$stmt->close();
?>
<!-- CDN Tailwind & jQuery -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="flex min-h-screen bg-gray-100 text-gray-800">
    <!-- SIDEBAR -->
    <div class="w-64 bg-[#F2A2A2] text-gray-800 flex flex-col justify-between shadow-lg">
        <div>
            <div class="text-center py-6 border-b border-pink-300/40">
                <div class="h-16 w-16 mx-auto bg-white rounded-full shadow-md overflow-hidden">
                    <img src="assets/images/user.jpg" class="h-16 w-16 rounded-full object-cover" alt="User Avatar">
                </div>
                <h2 class="mt-3 font-semibold text-lg"><?php echo $user['username']; ?></h2>
                <p class="text-sm text-gray-700"><?php echo $user['email']; ?></p>
            </div>

            <nav class="mt-6 space-y-2">
                <a href="dashboard.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium"> Dashboard</a>
                <a href="tasks.php" class="flex items-center px-6 py-2 rounded-md bg-white text-gray-900 font-semibold shadow-sm">My Task</a>
                <a href="categories.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">Task Categories</a>
                <a href="accountinfo.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">Account Info</a>
            </nav>
        </div>
        <a href="logout.php" class="flex items-center px-6 py-4 hover:bg-[#e87474] transition font-semibold border-t border-pink-300/40">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="flex-1 p-6 overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">My Tasks</h1>
            <p class="text-gray-500"><?php echo date("l, d/m/Y"); ?></p>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- LEFT: TASK LIST -->
            <div class="bg-white rounded-2xl shadow-md p-4">
                <h2 class="text-lg font-semibold mb-4">My Tasks</h2>
                <div id="task-list" class="space-y-3">
                    <?php foreach ($tasks as $t): ?>
                        <div class="task-item border rounded-lg p-3 flex justify-between items-center hover:shadow cursor-pointer transition"
                             data-title="<?php echo htmlspecialchars($t['title'] ?? '-'); ?>"
                             data-category="<?php echo htmlspecialchars($t['category_name'] ?? '-'); ?>"
                             data-subcategory="<?php echo htmlspecialchars($t['subcategory_name'] ?? '-'); ?>"
                             data-status="<?php echo htmlspecialchars($t['status'] ?? '-'); ?>"
                             data-description="<?php echo htmlspecialchars($t['description'] ?? '-'); ?>"
                             data-id="<?php echo $t['id']; ?>">
                            <div>
                                <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($t['title']); ?></h3>
                                <p class="text-sm text-gray-600 line-clamp-1"><?php echo htmlspecialchars(substr($t['description'], 0, 80)); ?>...</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <span class="font-medium text-gray-700">Status:</span> 
                                    <span class="<?php 
                                        echo ($t['status'] == 'Completed') ? 'text-green-600' : 
                                            (($t['status'] == 'In Progress') ? 'text-blue-600' : 'text-red-600'); ?>">
                                        <?php echo htmlspecialchars($t['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RIGHT: TASK DETAIL -->
            <div id="task-detail" class="bg-white rounded-2xl shadow-md p-6 hidden">
                <h2 class="text-xl font-semibold text-gray-800 mb-2" id="detail-title"></h2>
                <p class="text-sm text-gray-500 mb-3" id="detail-category"></p>
                <p class="text-sm mb-4">
                    <span class="font-semibold text-gray-700">Status:</span> 
                    <span id="detail-status"></span>
                </p>
                <p class="text-gray-700 text-sm leading-relaxed mb-6" id="detail-description"></p>
                
                <div class="flex gap-3">
                    <a href="updatetask.php?id=<?php echo $t['id']; ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Edit</a>

                    <a href="deletetask.php?id=<?php echo $t['id']; ?>" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition" onclick="return confirm('Yakin ingin menghapus task ini?')">Delete</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Saat klik task di kiri
    $(".task-item").on("click", function() {
        let title = $(this).data("title");
        let category = $(this).data("category") || "-";
        let subcategory = $(this).data("subcategory") || "-";
        let status = $(this).data("status");
        let description = $(this).data("description");
        let id = $(this).data("id");

        $("#detail-title").text(title);
        $("#detail-category").text(category + " / " + subcategory);
        $("#detail-status").text(status)
            .removeClass()
            .addClass(status == "Completed" ? "text-green-600" :
                      status == "In Progress" ? "text-blue-600" : "text-red-600");
        $("#detail-description").text(description);
        $("#edit-task").attr("href", "edit_task.php?id=" + id);
        $("#delete-task").attr("href", "delete_task.php?id=" + id);
        $("#task-detail").removeClass("hidden").hide().fadeIn(300);
    });
});
</script>
