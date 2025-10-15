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
//  AMBIL SEMUA KATEGORI UNTUK DITAMPILKAN
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


<h3>Tambah Kategori Baru</h3>
<form method="POST">
    <input type="text" name="category_name" placeholder="Nama kategori" required>
    <button type="submit" name="add_category">Tambah</button>
</form>

<?php if (!empty($categories)): ?>
    <h3>Tambah Subkategori</h3>
    <form method="POST">
        <select name="category_id" required>
            <option value="">-- Pilih kategori --</option>
            <?php foreach ($categories as $id => $cat): ?>
                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="subcategory_name" placeholder="Nama subkategori" required>
        <button type="submit" name="add_subcategory">Tambah</button>
    </form>
<?php endif; ?>

