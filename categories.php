<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// ambil data user dari database
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// kalau user tidak ditemukan
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
        if ($pst->execute()) {
            $message = "Kategori berhasil diperbarui!";
        } else {
            $message = "Gagal memperbarui kategori: " . $conn->error;
        }
        $pst->close();
    } else {
        $message = "Data update kategori tidak lengkap!";
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
        if ($pst->execute()) {
            $message = "Subkategori berhasil diperbarui!";
        } else {
            $message = "Gagal memperbarui subkategori: " . $conn->error;
        }
        $pst->close();
    } else {
        $message = "Data update subkategori tidak lengkap!";
    }
}

// =====================
//  DELETE KATEGORI
// =====================
if (isset($_POST['delete_category'])) {
    $cat_id = intval($_POST['category_id']);
    if ($cat_id > 0) {
        // Hapus subkategori dulu (agar tidak orphan)
        $conn->query("DELETE FROM subcategories WHERE category_id = $cat_id");
        $pst = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $pst->bind_param("ii", $cat_id, $user_id);
        if ($pst->execute()) {
            $message = "Kategori (beserta subkategori) berhasil dihapus!";
        } else {
            $message = "Gagal menghapus kategori: " . $conn->error;
        }
        $pst->close();
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
        if ($pst->execute()) {
            $message = "Subkategori berhasil dihapus!";
        } else {
            $message = "Gagal menghapus subkategori: " . $conn->error;
        }
        $pst->close();
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
                <a href="dashboard.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium"> Dashboard</a>
                <a href="tasks.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">My Task</a>
                <a href="categories.php" class="flex items-center px-6 py-2 rounded-md bg-white text-gray-900 font-semibold shadow-sm">Task Categories</a>
                <a href="accountinfo.php" class="flex items-center px-6 py-2 rounded-md hover:bg-[#f48c8c] transition font-medium">Account Info</a>
            </nav>
        </div>
        <a href="logout.php" class="flex items-center px-6 py-4 hover:bg-[#e87474] transition font-semibold border-t border-pink-300/40">Logout</a>
    </div>

    <!-- Main Content -->
    <main class="flex-1 p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Task Categories</h1>
            <a href="addcategories.php" class="bg-[#F2A2A2] hover:bg-[#f48c8c] text-white px-4 py-2 rounded-lg shadow">
                + Add Category
            </a>
        </div>

        <!-- Tabel Kategori -->
        <?php if (empty($categories)): ?>
            <p class="text-gray-600 italic">Belum ada kategori.</p>
        <?php else: ?>
            <?php foreach ($categories as $cid => $cat): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <!-- Tombol Tambah Subkategori -->
                    <div class="mt-4 text-right">
                        <a href="addsubcategories.php?category_id=<?php echo $cid; ?>"
                        class="inline-block bg-[#F2A2A2] hover:bg-[#f48c8c] text-white px-4 py-2 rounded-lg shadow transition">
                            + Add Subcategory
                        </a>
                    </div>

                    <!-- Header kategori -->
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <h2 class="text-xl font-semibold text-gray-700">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </h2>
                        <div class="space-x-2">
                            <!-- Form update kategori -->
                            <form method="POST" class="inline-block category-form">
                                <input type="hidden" name="category_id" value="<?php echo $cid; ?>">
                                <input type="text" name="new_category_name"
                                       placeholder="Edit nama kategori"
                                       class="hidden border px-2 py-1 rounded w-40">
                                <button type="submit" name="update_category"
                                        class="hidden bg-blue-500 text-white px-3 py-1 rounded">Save</button>
                            </form>
                            <button class="edit-category bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded">
                                Edit
                            </button>
                            <form method="POST" class="inline-block">
                                <input type="hidden" name="category_id" value="<?php echo $cid; ?>">
                                <button type="submit" name="delete_category"
                                        onclick="return confirm('Yakin hapus kategori ini?')"
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">Delete</button>
                            </form>
                        </div>
                    </div>

                    <!-- Subkategori -->
                    <h3 class="text-gray-600 font-semibold mb-2">Subcategories</h3>
                    <table class="min-w-full text-sm border">
                        <thead class="bg-gray-200 text-gray-700">
                            <tr>
                                <th class="py-2 px-3 border w-12 text-center">#</th>
                                <th class="py-2 px-3 border">Name</th>
                                <th class="py-2 px-3 border text-center w-48">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($cat['subs'])): ?>
                            <?php $i = 1; foreach ($cat['subs'] as $sub): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-3 border text-center"><?php echo $i++; ?></td>
                                    <td class="py-2 px-3 border"><?php echo htmlspecialchars($sub['name']); ?></td>
                                    <td class="py-2 px-3 border text-center space-x-2">
                                        <form method="POST" class="inline-block subcategory-form">
                                            <input type="hidden" name="subcategory_id" value="<?php echo $sub['id']; ?>">
                                            <input type="text" name="new_subcategory_name"
                                                   placeholder="Edit nama subkategori"
                                                   class="hidden border px-2 py-1 rounded w-40">
                                            <button type="submit" name="update_subcategory"
                                                    class="hidden bg-blue-500 text-white px-3 py-1 rounded">Save</button>
                                        </form>
                                        <button class="edit-subcategory bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded">
                                            Edit
                                        </button>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="subcategory_id" value="<?php echo $sub['id']; ?>">
                                            <button type="submit" name="delete_subcategory"
                                                    onclick="return confirm('Hapus subkategori ini?')"
                                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-gray-500 italic py-3">
                                    Belum ada subkategori.
                                </td>
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
    // Toggle edit kategori
    $('.edit-category').on('click', function () {
        const form = $(this).siblings('.category-form');
        form.find('input, button[name="update_category"]').toggleClass('hidden');
    });

    // Toggle edit subkategori
    $('.edit-subcategory').on('click', function () {
        const form = $(this).siblings('.subcategory-form');
        form.find('input, button[name="update_subcategory"]').toggleClass('hidden');
    });
});
</script>
</body>
</html>
