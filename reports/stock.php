<?php
include "../includes/db.php";

// دریافت فیلترها
$modelFilter = $_GET['model'] ?? '';
$colorFilter = $_GET['color'] ?? '';
$sizeFilter  = $_GET['size'] ?? '';

// دریافت مقادیر برای لیست فیلترها
$models = $db->query("SELECT DISTINCT model FROM products ORDER BY model ASC")->fetchAll(PDO::FETCH_COLUMN);
$colors = $db->query("SELECT name FROM colors ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$sizes  = $db->query("SELECT DISTINCT size FROM product_variants ORDER BY size ASC")->fetchAll(PDO::FETCH_COLUMN);

// ساخت کوئری با فیلتر
$query = "SELECT v.quantity, v.size, p.model, p.price, c.name as color
          FROM product_variants v
          JOIN products p ON v.product_id = p.id
          JOIN colors c ON v.color_id = c.id
          WHERE 1=1";

$params = [];

if ($modelFilter) {
    $query .= " AND p.model = ?";
    $params[] = $modelFilter;
}

if ($colorFilter) {
    $query .= " AND c.name = ?";
    $params[] = $colorFilter;
}

if ($sizeFilter) {
    $query .= " AND v.size = ?";
    $params[] = $sizeFilter;
}

$query .= " ORDER BY p.model ASC, c.name ASC, v.size ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// مجموع موجودی
$totalStock = array_sum(array_column($variants, 'quantity'));
?>

<?php include "../includes/header.php"; ?>

<h2>گزارش موجودی انبار</h2>

<a href="../index.php" style="display:inline-block; margin-bottom:20px;">
    &larr; بازگشت به داشبورد مدیریت انبار
</a>

<!-- فیلترها -->
<div style="background:#f8f9fa; padding:15px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); margin-bottom:20px;">

    <form method="get" style="display:flex; flex-wrap:wrap; gap:15px; align-items:center;">

        <label>
            مدل:
            <select name="model">
                <option value="">همه</option>
                <?php foreach ($models as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $modelFilter === $m ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            رنگ:
            <select name="color">
                <option value="">همه</option>
                <?php foreach ($colors as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $colorFilter === $c ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            سایز:
            <select name="size">
                <option value="">همه</option>
                <?php foreach ($sizes as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= $sizeFilter === $s ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit" style="padding:5px 15px; background:#007bff; color:white; border:none; border-radius:6px; cursor:pointer;">
            فیلتر
        </button>

    </form>
</div>

<!-- جمع موجودی -->
<div style="text-align:center; margin:20px 0;">
    <div style="display:inline-block; background:#007bff; color:white; padding:15px 30px; border-radius:8px; font-size:18px; font-weight:bold; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
        📦 مجموع موجودی انبار: <?= $totalStock ?>
    </div>
</div>

<!-- جدول موجودی -->
<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; margin-top:15px;">

    <thead style="background:#f1f1f1;">
        <tr>
            <th>مدل</th>
            <th>رنگ</th>
            <th>سایز</th>
            <th>قیمت</th>
            <th>تعداد</th>
        </tr>
    </thead>

    <tbody>

        <?php if (count($variants) > 0): ?>

            <?php foreach ($variants as $v): ?>

                <?php
                    $rowStyle = '';

                    if ($v['quantity'] <= 2) {
                        $rowStyle = 'background:white; color:black; font-weight:bold; border:2px solid red;';
                    }
                    elseif ($v['quantity'] < 5) {
                        $rowStyle = 'background:#ffc107; color:black;';
                    }
                ?>

                <tr style="<?= $rowStyle ?>">
                    <td><?= htmlspecialchars($v['model']) ?></td>
                    <td><?= htmlspecialchars($v['color']) ?></td>
                    <td><?= htmlspecialchars($v['size']) ?></td>
                    <td><?= number_format($v['price'], 2) ?></td>
                    <td><?= $v['quantity'] ?></td>
                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="5" style="text-align:center;">
                    هیچ موردی برای فیلترهای انتخاب‌شده یافت نشد
                </td>
            </tr>

        <?php endif; ?>

    </tbody>

</table>

<?php include "../includes/footer.php"; ?>