<?php
include "../includes/db.php";

$id = intval($_GET['id'] ?? 0);

// fetch product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("کالا یافت نشد.");
}

// fetch variants
$stmt = $db->prepare("
    SELECT v.id, v.size, v.quantity, v.image, c.name AS color
    FROM product_variants v
    LEFT JOIN colors c ON v.color_id = c.id
    WHERE v.product_id = ?
    ORDER BY v.id ASC
");
$stmt->execute([$id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// handle deletion of a variant
if (isset($_GET['delete_variant'])) {
    $variantId = intval($_GET['delete_variant']);

    $stmt = $db->prepare("DELETE FROM product_variants WHERE id=?");
    $stmt->execute([$variantId]);

    $stmt = $db->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id=?");
    $stmt->execute([$id]);

    if ($stmt->fetchColumn() == 0) {
        $stmt = $db->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$id]);

        header("Location: list.php");
        exit;
    }

    header("Location: edit.php?id=$id");
    exit;
}

// handle form submit
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $model = trim($_POST['model'] ?? '');
    $price = $_POST['price'] ?? '';

    if (!$model) $errors['model'] = "لطفاً نام کالا را وارد کنید.";

    if ($price === '' || !is_numeric($price) || floatval($price) <= 0) {
        $errors['price'] = "لطفاً قیمت معتبر وارد کنید.";
    } else {
        $price = floatval($price);
    }

    $colors = $_POST['color'] ?? [];
    $sizes = $_POST['size'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $images = $_FILES['variant_image'] ?? null;
    $variantIds = $_POST['variant_id'] ?? [];

    foreach ($colors as $i => $color) {
        $quantity = $quantities[$i] ?? '';

        if ($quantity === '' || !is_numeric($quantity) || intval($quantity) <= 0) {
            $errors['quantity'][$i] = "تعداد معتبر وارد کنید.";
        }

        if (!trim($color)) $errors['color'][$i] = "رنگ نمی‌تواند خالی باشد.";
        if (!trim($sizes[$i] ?? '')) $errors['size'][$i] = "سایز نمی‌تواند خالی باشد.";
    }

    if (empty($errors)) {

        $stmt = $db->prepare("UPDATE products SET model=?, price=? WHERE id=?");
        $stmt->execute([$model, $price, $id]);

        foreach ($variantIds as $i => $vid) {

            $colorName = trim($colors[$i]);

            $stmt = $db->prepare("SELECT id FROM colors WHERE name=?");
            $stmt->execute([$colorName]);

            $color = $stmt->fetch();
            $colorId = $color ? $color['id'] : null;

            if (!$colorId) {
                $stmt = $db->prepare("INSERT INTO colors (name) VALUES (?)");
                $stmt->execute([$colorName]);
                $colorId = $db->lastInsertId();
            }

            $variantImagePath = null;

            if (!empty($images['name'][$i])) {
                $filename = basename($images['name'][$i]);
                $targetFile = __DIR__ . "/../uploads/" . $filename;

                if (move_uploaded_file($images['tmp_name'][$i], $targetFile)) {
                    $variantImagePath = 'uploads/' . $filename;
                }
            }

            if ($variantImagePath) {
                $stmt = $db->prepare("UPDATE product_variants SET size=?, color_id=?, quantity=?, image=? WHERE id=?");
                $stmt->execute([$sizes[$i], $colorId, intval($quantities[$i]), $variantImagePath, $vid]);
            } else {
                $stmt = $db->prepare("UPDATE product_variants SET size=?, color_id=?, quantity=? WHERE id=?");
                $stmt->execute([$sizes[$i], $colorId, intval($quantities[$i]), $vid]);
            }
        }

        // handle new variants
        $newColors = $_POST['new_color'] ?? [];
        $newSizes = $_POST['new_size'] ?? [];
        $newQuantities = $_POST['new_quantity'] ?? [];
        $newImages = $_FILES['new_variant_image'] ?? null;

        foreach ($newColors as $i => $colorName) {

            $colorName = trim($colorName);

            if (!$colorName || !trim($newSizes[$i] ?? '') || !intval($newQuantities[$i] ?? 0)) continue;

            $stmt = $db->prepare("SELECT id FROM colors WHERE name=?");
            $stmt->execute([$colorName]);

            $color = $stmt->fetch();
            $colorId = $color ? $color['id'] : null;

            if (!$colorId) {
                $stmt = $db->prepare("INSERT INTO colors (name) VALUES (?)");
                $stmt->execute([$colorName]);
                $colorId = $db->lastInsertId();
            }

            $variantImagePath = null;

            if (!empty($newImages['name'][$i])) {
                $filename = basename($newImages['name'][$i]);
                $targetFile = __DIR__ . "/../uploads/" . $filename;

                if (move_uploaded_file($newImages['tmp_name'][$i], $targetFile)) {
                    $variantImagePath = 'uploads/' . $filename;
                }
            }

            $stmt = $db->prepare("INSERT INTO product_variants (product_id, color_id, size, quantity, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $colorId, $newSizes[$i], intval($newQuantities[$i]), $variantImagePath]);
        }

        header("Location: list.php");
        exit;
    }
}
?>

<?php include "../includes/header.php"; ?>

<h2>ویرایش کالا و مشخصات انبار</h2>

<a href="list.php" style="display:inline-block; margin-bottom:20px;">
    &larr; بازگشت به لیست کالاها
</a>

<form action="" method="post" enctype="multipart/form-data" style="max-width:900px; margin:auto;">

    <label>نام کالا:<br>
        <input type="text" name="model" value="<?= htmlspecialchars($product['model']) ?>" required style="width:100%; padding:8px;">
    </label><br><br>

    <label>قیمت:<br>
        <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required style="width:100%; padding:8px;">
    </label><br><br>

    <h3>مشخصات موجود در انبار</h3>

    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:10px;">
        <thead>
            <tr>
                <th>رنگ</th>
                <th>سایز</th>
                <th>تعداد</th>
                <th>تصویر</th>
                <th>عملیات</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($variants as $i => $v): ?>
            <tr>
                <td>
                    <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">
                    <input type="text" name="color[]" value="<?= htmlspecialchars($v['color']) ?>" required>
                </td>

                <td><input type="text" name="size[]" value="<?= htmlspecialchars($v['size']) ?>" required></td>

                <td><input type="number" name="quantity[]" value="<?= $v['quantity'] ?>" min="1" required></td>

                <td>
                    <?php if (!empty($v['image'])): ?>
                        <img src="../<?= htmlspecialchars($v['image']) ?>" width="50"><br>
                    <?php endif; ?>
                    <input type="file" name="variant_image[]">
                </td>

                <td>
                    <a href="edit.php?id=<?= $id ?>&delete_variant=<?= $v['id'] ?>"
                       style="color:red;"
                       onclick="return confirm('آیا از حذف این مشخصه مطمئن هستید؟');">
                        🗑 حذف
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h3>افزودن مشخصات جدید به انبار</h3>

    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:10px;">
        <thead>
            <tr>
                <th>رنگ</th>
                <th>سایز</th>
                <th>تعداد</th>
                <th>تصویر</th>
                <th>عملیات</th>
            </tr>
        </thead>

        <tbody id="newVariantsBody"></tbody>
    </table>

    <button type="button" onclick="addNewVariant()">➕ افزودن مشخصه</button>

    <br><br>

    <button type="submit"
            style="padding:10px 20px; background:#007BFF; color:white; border:none; border-radius:6px; cursor:pointer;">
        بروزرسانی کالا
    </button>

</form>

<script>
function addNewVariant() {
    const tbody = document.getElementById('newVariantsBody');
    const row = document.createElement('tr');

    row.innerHTML = `
        <td><input type="text" name="new_color[]" required></td>
        <td><input type="text" name="new_size[]" required></td>
        <td><input type="number" name="new_quantity[]" min="1" required></td>
        <td><input type="file" name="new_variant_image[]"></td>
        <td><button type="button" onclick="this.closest('tr').remove()">حذف</button></td>
    `;

    tbody.appendChild(row);
}
</script>

<?php include "../includes/footer.php"; ?>