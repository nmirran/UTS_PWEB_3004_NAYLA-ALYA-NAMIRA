<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Ambil data user
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Jika user tidak ditemukan
if (!$user) {
    die("Data pengguna tidak ditemukan!");
}

// =====================
//  UPDATE KATEGORI
// =====================
if (isset($_POST['update_category'])) {
    $cat_id = intval($_POST['category_id']);
    $new_name = trim($_POST['new_category_name'] ?? '');
    if ($cat_id > 0 && $new_name !== '') {
        $pst = $conn->prepare("UPDATE categories SET name = ? WHERE id = ? AND user_id = ?");
        $pst->bind_param("sii", $new_name, $cat_id, $user_id);
        $pst->execute();
        $message = "Kategori berhasil diperbarui!";
        $pst->close();
    }
}

// =====================
//  UPDATE SUBKATEGORI
// =====================
if (isset($_POST['update_subcategory'])) {
    $sub_id = intval($_POST['subcategory_id']);
    $new_name = trim($_POST['new_subcategory_name'] ?? '');
    if ($sub_id > 0 && $new_name !== '') {
        $pst = $conn->prepare("
            UPDATE subcategories 
            SET name = ? 
            WHERE id = ? AND category_id IN (SELECT id FROM categories WHERE user_id = ?)
        ");
        $pst->bind_param("sii", $new_name, $sub_id, $user_id);
        $pst->execute();
        $message = "Subkategori berhasil diperbarui!";
        $pst->close();
    }
}

// =====================
//  DELETE KATEGORI
// =====================
if (isset($_POST['delete_category'])) {
    $cat_id = intval($_POST['category_id']);
    if ($cat_id > 0) {
        $conn->query("DELETE FROM subcategories WHERE category_id = $cat_id");
        $pst = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $pst->bind_param("ii", $cat_id, $user_id);
        $pst->execute();
        $pst->close();
        $message = "Kategori (beserta subkategori) berhasil dihapus!";
    }
}

// =====================
//  DELETE SUBKATEGORI
// =====================
if (isset($_POST['delete_subcategory'])) {
    $sub_id = intval($_POST['subcategory_id']);
    if ($sub_id > 0) {
        $pst = $conn->prepare("
            DELETE FROM subcategories 
            WHERE id = ? AND category_id IN (SELECT id FROM categories WHERE user_id = ?)
        ");
        $pst->bind_param("ii", $sub_id, $user_id);
        $pst->execute();
        $pst->close();
        $message = "Subkategori berhasil dihapus!";
    }
}

// =====================
//  AMBIL SEMUA DATA
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
    if ($row['subcategory_id']) {
        $categories[$cid]['subs'][] = [
            'id' => $row['subcategory_id'],
            'name' => $row['subcategory_name']
        ];
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Task Categories</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
    body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-inter">

<div class="flex min-h-screen">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#F2A2A2] flex flex-col justify-between shadow-md">
        <div>
            <div class="text-center py-6 border-b border-pink-300/40">
                <div class="h-16 w-16 mx-auto bg-white rounded-full shadow-md overflow-hidden">
                    <img src="assets/images/user.jpg" alt="User Avatar" class="h-full w-full object-cover">
                </div>
                <h2 class="mt-3 font-semibold text-lg"><?php echo htmlspecialchars($user['username']); ?></h2>
                <p class="text-sm text-gray-700"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <nav class="mt-6 space-y-1">
                <a href="dashboard.php" class="block px-6 py-2 hover:bg-[#f48c8c] rounded transition">Dashboard</a>
                <a href="tasks.php" class="block px-6 py-2 hover:bg-[#f48c8c] rounded transition">My Task</a>
                <a href="categories.php" class="block px-6 py-2 bg-white text-gray-900 font-semibold shadow-sm rounded">Task Categories</a>
                <a href="accountinfo.php" class="block px-6 py-2 hover:bg-[#f48c8c] rounded transition">Account Info</a>
            </nav>
        </div>

        <!-- Logout di bawah account info -->
        <a href="logout.php" class="block px-6 py-4 hover:bg-[#e87474] border-t border-pink-300/40 font-semibold transition text-center">Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Task Categories</h1>
            <a href="addcategories.php" class="bg-[#F2A2A2] hover:bg-[#f48c8c] text-white px-4 py-2 rounded-lg shadow transition">
                + Add Category
            </a>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded-md mb-6 shadow-sm border border-green-200">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($categories)): ?>
            <p class="text-gray-600 italic">Belum ada kategori.</p>
        <?php else: ?>
            <?php foreach ($categories as $cid => $cat): ?>
                <div class="bg-white rounded-xl shadow-md p-6 mb-8 border border-gray-200">
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <h2 class="text-xl font-semibold text-gray-700">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </h2>
                        <div class="space-x-2 flex items-center">
                            <form method="POST" class="category-form hidden sm:inline-block">
                                <input type="hidden" name="category_id" value="<?php echo $cid; ?>">
                                <input type="text" name="new_category_name"
                                    class="border px-2 py-1 rounded w-44 text-sm" placeholder="Edit nama...">
                                <button type="submit" name="update_category"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">Save</button>
                            </form>
                            <button class="edit-category bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-sm">Edit</button>
                            <form method="POST" class="inline-block">
                                <input type="hidden" name="category_id" value="<?php echo $cid; ?>">
                                <button type="submit" name="delete_category"
                                    onclick="return confirm('Yakin hapus kategori ini?')"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Delete</button>
                            </form>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-gray-600 font-semibold">Subcategories</h3>
                        <a href="addsubcategories.php?category_id=<?php echo $cid; ?>"
                            class="bg-[#F2A2A2] hover:bg-[#f48c8c] text-white px-3 py-1 rounded-lg shadow text-sm">
                            + Add Subcategory
                        </a>
                    </div>

                    <table class="min-w-full text-sm border rounded-md overflow-hidden">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="py-2 px-3 border w-12 text-center">#</th>
                                <th class="py-2 px-3 border text-left">Name</th>
                                <th class="py-2 px-3 border text-center w-44">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cat['subs'])): $i=1; foreach ($cat['subs'] as $sub): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-3 border text-center"><?php echo $i++; ?></td>
                                    <td class="py-2 px-3 border"><?php echo htmlspecialchars($sub['name']); ?></td>
                                    <td class="py-2 px-3 border text-center space-x-1">
                                        <form method="POST" class="subcategory-form hidden inline-block">
                                            <input type="hidden" name="subcategory_id" value="<?php echo $sub['id']; ?>">
                                            <input type="text" name="new_subcategory_name"
                                                class="border px-2 py-1 rounded w-36 text-sm" placeholder="Edit nama...">
                                            <button type="submit" name="update_subcategory"
                                                class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Save</button>
                                        </form>
                                        <button class="edit-subcategory bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-sm">Edit</button>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="subcategory_id" value="<?php echo $sub['id']; ?>">
                                            <button type="submit" name="delete_subcategory"
                                                onclick="return confirm('Hapus subkategori ini?')"
                                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-gray-500 italic py-3">Belum ada subkategori.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<script>
$(document).ready(function () {
    $('.edit-category').on('click', function () {
        $(this).siblings('.category-form').toggleClass('hidden');
    });

    $('.edit-subcategory').on('click', function () {
        $(this).siblings('.subcategory-form').toggleClass('hidden');
    });
});
</script>
</body>
</html>
