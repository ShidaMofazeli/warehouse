<?php
include "../includes/db.php";
require_once "../includes/jdf.php"; // کتابخانه تاریخ شمسی

$filter = $_GET['filter'] ?? 'daily';
$date   = $_GET['date'] ?? null;

$query = "SELECT s.id, s.sale_date, s.quantity, p.model, p.price 
          FROM sales s 
          JOIN product_variants v ON s.variant_id = v.id
          JOIN products p ON v.product_id = p.id";

$params = [];

if ($date) {
    $query .= " WHERE DATE(s.sale_date) = DATE(?)";
    $params[] = $date;

} elseif ($filter === 'daily') {

    $query .= " WHERE DATE(s.sale_date) = DATE('now')";

} elseif ($filter === 'monthly') {

    $query .= " WHERE strftime('%Y-%m', s.sale_date) = strftime('%Y-%m', 'now')";
}

$query .= " ORDER BY s.sale_date DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// محاسبه مجموع فروش
$totalSum = 0;

foreach ($sales as $s) {
    $totalSum += $s['price'] * $s['quantity'];
}
?>

<?php include "../includes/header.php"; ?>

<h2>گزارش تاریخچه فروش انبار</h2>

<a href="../index.php" style="display:inline-block; margin-bottom:20px;">
    &larr; بازگشت به داشبورد مدیریت انبار
</a>

<!-- فیلترها -->
<form method="get" style="margin-bottom:20px;">

    <label>
        انتخاب تاریخ:
        <input type="date" name="date" value="<?= htmlspecialchars($date ?? '') ?>">
    </label>

    <button type="submit">فیلتر</button>

    <a href="history.php?filter=daily">امروز</a> | 
    <a href="history.php?filter=monthly">این ماه</a>

</form>

<!-- جمع کل فروش -->
<div style="text-align:center; margin:20px 0;">
    <div style="display:inline-block; background:#007bff; color:white; padding:15px 30px; border-radius:8px; font-size:18px; font-weight:bold; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
        💰 مجموع فروش: <?= number_format($totalSum, 2) ?>
    </div>
</div>

<!-- جدول فروش -->
<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">

    <thead style="background:#f1f1f1;">
        <tr>
            <th>شناسه</th>
            <th>تاریخ (شمسی)</th>
            <th>ساعت</th>
            <th>نام کالا</th>
            <th>قیمت</th>
            <th>تعداد</th>
            <th>جمع کل</th>
        </tr>
    </thead>

    <tbody>

        <?php if (count($sales) > 0): ?>

            <?php foreach ($sales as $s): ?>

                <?php
                    $ts = strtotime($s['sale_date']);
                    $jalaliDate = jdate("Y/m/d", $ts);
                    $timeStr = date("H:i", $ts);
                ?>

                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><?= $jalaliDate ?></td>
                    <td><?= $timeStr ?></td>
                    <td><?= htmlspecialchars($s['model']) ?></td>
                    <td><?= number_format($s['price'], 2) ?></td>
                    <td><?= $s['quantity'] ?></td>
                    <td><?= number_format($s['price'] * $s['quantity'], 2) ?></td>
                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="7" style="text-align:center;">
                    هیچ فروشی برای بازه انتخاب‌شده یافت نشد
                </td>
            </tr>

        <?php endif; ?>

    </tbody>

</table>

<?php include "../includes/footer.php"; ?>