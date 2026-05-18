<?php
include "../includes/db.php";

// --- حذف مشخصه ---
if (isset($_GET['delete'])) {
    $variantId = (int) $_GET['delete'];

    $stmt = $db->prepare("SELECT product_id, image FROM product_variants WHERE id = ?");
    $stmt->execute([$variantId]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($variant) {
        $productId = $variant['product_id'];

        if (!empty($variant['image']) && file_exists(__DIR__ . "/../" . $variant['image'])) {
            unlink(__DIR__ . "/../" . $variant['image']);
        }

        $stmt = $db->prepare("DELETE FROM product_variants WHERE id = ?");
        $stmt->execute([$variantId]);

        $stmt = $db->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
        $stmt->execute([$productId]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {

            $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($product['image']) && file_exists(__DIR__ . "/../" . $product['image'])) {
                unlink(__DIR__ . "/../" . $product['image']);
            }

            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
        }
    }

    header("Location: list.php");
    exit;
}

// --- دریافت کالاها و مشخصات ---
$stmt = $db->query("
    SELECT p.id AS product_id, p.model, p.price, p.image AS product_image,
           v.id AS variant_id, v.size, v.quantity, v.image AS variant_image, 
           c.name AS color
    FROM products p
    INNER JOIN product_variants v ON p.id = v.product_id
    LEFT JOIN colors c ON v.color_id = c.id
    ORDER BY p.id DESC, v.id ASC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- گروه‌بندی کالاها ---
$products = [];

foreach ($rows as $row) {
    $products[$row['product_id']]['info'] = [
        'model' => $row['model'],
        'price' => $row['price'],
        'image' => $row['product_image']
    ];

    $products[$row['product_id']]['variants'][] = [
        'id' => $row['variant_id'],
        'size' => $row['size'],
        'color' => $row['color'],
        'quantity' => $row['quantity'],
        'image' => $row['variant_image']
    ];
}
?>

<?php include "../includes/header.php"; ?>

<h2>همه کالاهای انبار</h2>

<a href="add.php" style="display:inline-block; margin-bottom:20px;">
    ➕ افزودن کالای جدید
</a>

<?php foreach ($products as $pid => $product): ?>
    <?php if (!empty($product['variants'])): ?>

    <div style="border:1px solid #ccc; padding:15px; margin-bottom:20px; border-radius:8px;">

        <h3>
            <?= htmlspecialchars($product['info']['model']) ?>
            (<?= number_format($product['info']['price'], 2) ?>)
        </h3>

        <?php if (!empty($product['info']['image'])): ?>
            <img src="../<?= htmlspecialchars($product['info']['image']) ?>" alt="کالا" width="100" style="margin-bottom:10px;">
        <?php endif; ?>

        <table border="1" cellpadding="5" cellspacing="0"
               style="width:100%; border-collapse:collapse; margin-top:10px;">

            <thead>
                <tr>
                    <th>سایز</th>
                    <th>رنگ</th>
                    <th>تعداد</th>
                    <th>تصویر</th>
                    <th>عملیات</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($product['variants'] as $v): ?>
                    <tr>

                        <td><?= htmlspecialchars($v['size']) ?></td>

                        <td><?= htmlspecialchars($v['color'] ?? '-') ?></td>

                        <td><?= htmlspecialchars($v['quantity']) ?></td>

                        <td>
                            <?php if (!empty($v['image'])): ?>
                                <img src="../<?= htmlspecialchars($v['image']) ?>" alt="مشخصه" width="50">
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="edit.php?id=<?= $pid ?>"
                               style="color:blue; margin-right:10px;">
                                ✏ ویرایش
                            </a>

                            <a href="list.php?delete=<?= $v['id'] ?>"
                               style="color:red;"
                               onclick="return confirm('آیا از حذف این مشخصه مطمئن هستید؟');">
                                🗑 حذف
                            </a>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>

    </div>

    <?php endif; ?>
<?php endforeach; ?>

<?php include "../includes/footer.php"; ?>