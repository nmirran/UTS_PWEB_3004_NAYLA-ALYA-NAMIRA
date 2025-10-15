<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// =====================
//  TAMBAH KATEGORI
// =====================
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    if ($name !== '') {
        $pst = $conn->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
        $pst->bind_param("is", $user_id, $name);
        if ($pst->execute()) {
            $message = "Kategori '$name' berhasil ditambahkan!";
        } else {
            $message = "Gagal menambah kategori: " . $conn->error;
        }
        $pst->close();
    } else {
        $message = "Nama kategori tidak boleh kosong!";
    }
}

// =====================
//  TAMBAH SUBKATEGORI
// =====================
if (isset($_POST['add_subcategory'])) {
    $category_id = intval($_POST['category_id'] ?? 0);
    $sub_name = trim($_POST['subcategory_name'] ?? '');
    if ($category_id > 0 && $sub_name !== '') {
        $pst = $conn->prepare("INSERT INTO subcategories (category_id, name) VALUES (?, ?)");
        $pst->bind_param("is", $category_id, $sub_name);
        if ($pst->execute()) {
            $message = "Subkategori '$sub_name' berhasil ditambahkan!";
            header("Location: categories.php");
            exit();
        } else {
            $message = "Gagal menambah subkategori: " . $conn->error;
        }
        $pst->close();
    } else {
        $message = "Data subkategori tidak lengkap!";
    }
}

// =====================
//  AMBIL SEMUA KATEGORI
// =====================
$sql = "
    SELECT c.id AS category_id, c.name AS category_name, 
           s.id AS subcategory_id, s.name AS subcategory_name
    FROM categories c
    LEFT JOIN subcategories s ON c.id = s.category_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC, s.created_at ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$categories = [];
while ($row = $result->fetch_assoc()) {
    $cid = $row['category_id'];
    if (!isset($categories[$cid])) {
        $categories[$cid] = [
            'name' => $row['category_name'],
            'subs' => []
        ];
    }
    if ($row['subcategory_name']) {
        $categories[$cid]['subs'][] = $row['subcategory_name'];
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kategori & Subkategori</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-b from-pink-100 via-white to-pink-50 flex items-center justify-center py-10">

<div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl p-8 relative">
    <h1 class="text-3xl font-bold text-pink-600 mb-6 text-center">Kelola Kategori</h1>

    <?php if ($message): ?>
        <div id="alertBox" class="mb-5 bg-pink-100 border border-pink-300 text-pink-700 px-4 py-3 rounded-lg text-center">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- FORM TAMBAH KATEGORI -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-3">Tambah Kategori Baru</h3>
        <form method="POST" class="flex gap-3 items-center">
            <input type="text" name="category_name" placeholder="Nama kategori"
                class="flex-1 border border-pink-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400"
                required>
            <button type="submit" name="add_category"
                class="bg-pink-500 hover:bg-pink-600 text-white font-semibold px-4 py-2 rounded-lg transition">
                Tambah
            </button>
        </form>
    </div>

    <!-- FORM TAMBAH SUBKATEGORI -->
    <?php if (!empty($categories)): ?>
        <div>
            <h3 class="text-xl font-semibold text-gray-800 mb-3">Tambah Subkategori</h3>
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <select name="category_id" required
                    class="col-span-1 border border-pink-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400">
                    <option value="">-- Pilih kategori --</option>
                    <?php foreach ($categories as $id => $cat): ?>
                        <option value="<?= $id; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="subcategory_name" placeholder="Nama subkategori" required
                    class="col-span-1 border border-pink-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400">
                <button type="submit" name="add_subcategory"
                    class="bg-pink-500 hover:bg-pink-600 text-white font-semibold px-4 py-2 rounded-lg transition">
                    Tambah
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- LIST KATEGORI & SUB -->
    <?php if (!empty($categories)): ?>
        <div class="mt-10">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Daftar Kategori</h3>
            <div class="space-y-4">
                <?php foreach ($categories as $cat): ?>
                    <div class="bg-pink-50 border border-pink-200 rounded-xl p-4 shadow-sm">
                        <h4 class="text-pink-700 font-semibold"><?= htmlspecialchars($cat['name']); ?></h4>
                        <?php if (!empty($cat['subs'])): ?>
                            <ul class="list-disc list-inside text-gray-700 mt-2">
                                <?php foreach ($cat['subs'] as $sub): ?>
                                    <li><?= htmlspecialchars($sub); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm italic mt-1">Belum ada subkategori</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Notifikasi hilang otomatis
    $(document).ready(function() {
        setTimeout(() => {
            $("#alertBox").fadeOut(600);
        }, 2500);
    });
</script>

</body>
</html>
