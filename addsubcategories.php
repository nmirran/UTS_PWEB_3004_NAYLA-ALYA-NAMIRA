<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$category_id = $_GET['category_id'] ?? null;

if (!$category_id) {
    die("Kategori tidak ditemukan.");
}

// Ambil data user untuk sidebar
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil nama kategori (buat tampilan)
$stmt = $conn->prepare("SELECT name FROM categories WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $category_id, $user_id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Tambah subkategori
$message = "";
if (isset($_POST['add_subcategory'])) {
    $name = trim($_POST['name']);
    if ($name !== "") {
        $stmt = $conn->prepare("INSERT INTO subcategories (name, category_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("si", $name, $category_id);
        if ($stmt->execute()) {
            $message = "✅ Subkategori berhasil ditambahkan!";
        } else {
            $message = "❌ Gagal menambahkan subkategori: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Nama subkategori tidak boleh kosong.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Add Subcategory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <div class="w-64 bg-[#F2A2A2] text-gray-800 flex flex-col justify-between shadow-lg">
        <div>
            <div class="text-center py-6 border-b border-pink-300/40">
                <div class="h-16 w-16 mx-auto bg-white rounded-full shadow-md overflow-hidden">
                    <img src="assets/images/user.jpg" class="h-16 w-16 rounded-full object-cover" alt="User Avatar">
                </div>
                <h2 class="mt-3 font-semibold text-lg"><?php echo htmlspecialchars($user['username']); ?></h2>
                <p class="text-sm text-gray-700"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <nav class="mt-6 space-y-2">
                <a href="dashboard.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium"> Dashboard</a>
                <a href="tasks.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">My Task</a>
                <a href="categories.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">Task Categories</a>
                <a href="accountinfo.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">Account Info</a>
            </nav>
        </div>
        <a href="logout.php" class="flex items-center px-6 py-4 hover:bg-[#e87474] transition font-semibold border-t border-pink-300/40">Logout</a>
    </div>

    <!-- Main Content -->
    <main class="flex-1 p-10">
        <div class="bg-white shadow-lg rounded-xl p-8 max-w-xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Tambah Subkategori</h1>
            <p class="text-gray-600 mb-6">Untuk kategori: 
                <span class="font-semibold text-[#f48c8c]">
                    <?php echo htmlspecialchars($category['name'] ?? '(Tidak ditemukan)'); ?>
                </span>
            </p>

            <?php if ($message): ?>
                <div class="mb-4 p-3 rounded-md text-white 
                    <?php echo (str_starts_with($message, '✅')) ? 'bg-green-500' : 'bg-red-500'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">

                <label class="block mb-2 font-semibold">Nama Subkategori</label>
                <input type="text" name="name" required
                    class="w-full border rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-[#F2A2A2]">

                <div class="flex justify-between items-center">
                    <a href="categories.php"
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition">
                        ← Kembali
                    </a>
                    <button type="submit" name="add_subcategory"
                            class="bg-[#F2A2A2] hover:bg-[#f48c8c] text-white px-4 py-2 rounded-lg shadow transition">
                        Tambah Subkategori
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>

