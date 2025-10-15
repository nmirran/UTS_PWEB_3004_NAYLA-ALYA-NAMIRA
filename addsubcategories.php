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

// Ambil nama kategori
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
            $message = "Subkategori berhasil ditambahkan!";
        } else {
            $message = "Gagal menambahkan subkategori: " . $conn->error;
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
    <title>Tambah Subkategori</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body class="min-h-screen bg-gradient-to-b from-pink-100 via-white to-pink-50 text-gray-800 flex">

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="bg-white shadow-xl rounded-2xl p-8 w-full max-w-lg border border-pink-100">
            <h1 class="text-3xl font-bold text-pink-600 mb-2 text-center">Tambah Subkategori</h1>
            <p class="text-gray-600 mb-6 text-center">
                Untuk kategori: <span class="font-semibold text-pink-500">
                    <?php echo htmlspecialchars($category['name'] ?? '(Tidak ditemukan)'); ?>
                </span>
            </p>

            <?php if ($message): ?>
                <div id="alertBox" class="mb-5 text-center px-4 py-3 rounded-lg font-medium 
                    <?php echo (str_contains($message, 'berhasil')) ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">

                <label class="block mb-2 font-semibold text-gray-700">Nama Subkategori</label>
                <input type="text" name="name" required
                    class="w-full border border-pink-300 rounded-lg px-4 py-2 mb-5 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:border-transparent transition">

                <div class="flex justify-between items-center">
                    <a href="categories.php"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg transition font-medium">
                        ← Kembali
                    </a>
                    <button type="submit" name="add_subcategory"
                            class="bg-pink-500 hover:bg-pink-600 text-white px-5 py-2 rounded-lg font-semibold shadow-md transition">
                        Tambah Subkategori
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Hilangkan alert otomatis setelah 2.5 detik
        $(document).ready(() => {
            setTimeout(() => {
                $("#alertBox").fadeOut(600);
            }, 2500);
        });
    </script>
</body>
</html>