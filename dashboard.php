<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$user_query = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();
$user_query->close();

$tasks_query = $conn->prepare("
    SELECT t.id, t.title, t.description, t.status, 
           c.name AS category_name, s.name AS subcategory_name, t.created_at
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN subcategories s ON t.subcategory_id = s.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$tasks_query->bind_param("i", $user_id);
$tasks_query->execute();
$result = $tasks_query->get_result();

$not_completed = [];
$completed = [];

while ($row = $result->fetch_assoc()) {
    if (strtolower($row['status']) === 'completed') {
        $completed[] = $row;
    } else {
        $not_completed[] = $row;
    }
}
$tasks_query->close();

$count_total = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$count_completed = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id = $user_id AND STATUS='Completed'")->fetch_assoc()['total'] ?? 0;
$count_inprogress = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id = $user_id AND STATUS='In Progress'")->fetch_assoc()['total'] ?? 0;
$count_notstarted = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id = $user_id AND STATUS='Not Started'")->fetch_assoc()['total'] ?? 0;

$percent_completed = $count_total > 0 ? round(($count_completed / $count_total) * 100) : 0;
$percent_inprogress = $count_total > 0 ? round(($count_inprogress / $count_total) * 100) : 0;
$percent_notstarted = $count_total > 0 ? round(($count_notstarted / $count_total) * 100) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<div class="flex min-h-screen bg-gray-100 text-gray-800">
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
                <a href="dashboard.php" class="flex items-center px-6 py-2 rounded-md bg-white text-gray-900 font-semibold shadow-sm"> Dashboard</a>
                <a href="tasks.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">My Task</a>
                <a href="categories.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">Task Categories</a>
                <a href="accountinfo.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">Account Info</a>
            </nav>
        </div>
        <a href="logout.php" class="flex items-center px-6 py-4 hover:bg-[#e87474] transition font-semibold border-t border-pink-300/40">Logout</a>
    </div>

        <!-- MAIN CONTENT -->
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold">Welcome back, <?php echo $user['username']; ?> 👋</h1>
                <p id="datetime" class="text-gray-500"></p>
            </div>

            <div class="grid grid-cols-3 gap-6 mt-8">

                <!-- TO DO LIST -->
                <div class="col-span-2 bg-white p-6 rounded-2xl shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold mb-4">To-Do</h2>
                        <a href="addtask.php" class="inline-flex items-center gap-2 px-4 py-2 bg-[#F2A2A2] text-white rounded-lg hover:bg-[#f48c8c] transition-all duration-200 shadow-sm">
                            <span class="text-lg font-bold">+</span> Add Task
                        </a>
                    </div>
                    <?php if (count($not_completed) > 0): ?>
                        <?php foreach ($not_completed as $task): ?>
                            <div class="border-l-4 pl-4 mb-4 
                                <?php echo strtolower($task['status']) === 'in progress' ? 'border-blue-500' : 'border-red-500'; ?>">
                                <h3 class="font-semibold"><?php echo htmlspecialchars($task['title']); ?></h3>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($task['description']); ?></p>
                                <p class="text-sm mt-2">
                                    <span class="font-semibold">Status:</span> 
                                    <span class="text-gray-700"><?php echo $task['status']; ?></span>
                                </p>
                                <p class="text-xs text-gray-400">Created on: <?php echo date("d M Y", strtotime($task['created_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500">Tidak ada task belum selesai.</p>
                    <?php endif; ?>
                </div>

            <!-- TASK STATUS CHART -->
            <div class="bg-white p-6 rounded-2xl shadow-md relative text-center">
                <h2 class="text-xl font-semibold mb-4">Task Status</h2>
                <div class="flex justify-center items-center relative">
                    <canvas id="taskChart" width="200" height="200"></canvas>
                    <div id="chartCenterText" class="absolute text-2xl font-bold text-gray-700"></div>
                </div>
                <div class="flex justify-around mt-6 text-center">
                    <div class="flex flex-col items-center">
                        <div class="h-3 w-3 rounded-full bg-green-500 mb-1"></div>
                        <p class="text-sm text-gray-600">Completed</p>
                        <p class="font-semibold text-green-600"><?php echo $percent_completed; ?>%</p>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="h-3 w-3 rounded-full bg-blue-500 mb-1"></div>
                        <p class="text-sm text-gray-600">In Progress</p>
                        <p class="font-semibold text-blue-600"><?php echo $percent_inprogress; ?>%</p>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="h-3 w-3 rounded-full bg-red-500 mb-1"></div>
                        <p class="text-sm text-gray-600">Not Started</p>
                        <p class="font-semibold text-red-600"><?php echo $percent_notstarted; ?>%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- COMPLETED TASK -->
        <div class="bg-white p-6 mt-8 rounded-2xl shadow-md">
            <h2 class="text-xl font-semibold mb-4">Completed Task</h2>
            <?php if (count($completed) > 0): ?>
                <?php foreach ($completed as $task): ?>
                    <div class="border-l-4 border-green-500 pl-4 mb-4">
                        <h3 class="font-semibold"><?php echo htmlspecialchars($task['title']); ?></h3>
                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($task['description']); ?></p>
                        <p class="text-sm mt-2 text-green-600">Status: Completed</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500">Belum ada task yang selesai.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SCRIPT CHART.JS -->
<script>
const ctx = document.getElementById('taskChart');
const total = <?php echo $count_total; ?>;
const completed = <?php echo $count_completed; ?>;
const inprogress = <?php echo $count_inprogress; ?>;
const notstarted = <?php echo $count_notstarted; ?>;
const percentCompleted = <?php echo $percent_completed; ?>;

const chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'In Progress', 'Not Started'],
        datasets: [{
            data: [completed, inprogress, notstarted],
            backgroundColor: ['#22c55e', '#3b82f6', '#ef4444'],
            borderWidth: 2
        }]
    },
    options: {
        cutout: '75%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (ctx) => `${ctx.label}: ${ctx.parsed} task`
                }
            }
        }
    }
});

function updateDateTime() {
    const now = new Date();
    const options = { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' };
    document.getElementById('datetime').textContent = now.toLocaleDateString('en-GB', options);
}
updateDateTime();
setInterval(updateDateTime, 1000 * 60); // update tiap menit

// Tambah angka di tengah chart
document.getElementById('chartCenterText').innerText = percentCompleted + '%';
</script>

</body>
</html>
