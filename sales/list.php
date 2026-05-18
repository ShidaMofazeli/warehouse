<?php
include "../includes/db.php";
include "../includes/header.php";

// دریافت همه فروش‌ها همراه با جزئیات
$stmt = $db->query("
    SELECT 
        s.id, s.sale_date, s.quantity as sold_qty,
        p.model, p.price,
        v.size, c.name as color
    FROM sales s
    JOIN product_variants v ON s.variant_id = v.id
    JOIN products p ON v.product_id = p.id
    JOIN colors c ON v.color_id = c.id
    ORDER BY s.sale_date DESC
");

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>تاریخچه فروش انبار</h2>

<a href="../index.php">
    ← بازگشت به داشبورد مدیریت انبار
</a>

<br><br>

<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%;">

    <thead style="background:#f1f1f1;">
        <tr>
            <th>تاریخ</th>
            <th>ساعت</th>
            <th>کالا</th>
            <th>سایز</th>
            <th>رنگ</th>
            <th>قیمت</th>
            <th>تعداد</th>
            <th>جمع کل</th>
            <th>فاکتور</th>
        </tr>
    </thead>

    <tbody>

        <?php if (count($sales) > 0): ?>

            <?php foreach($sales as $s): ?>

                <tr>
                    <td><?= date('Y-m-d', strtotime($s['sale_date'])) ?></td>

                    <td><?= date('H:i:s', strtotime($s['sale_date'])) ?></td>

                    <td><?= htmlspecialchars($s['model']) ?></td>

                    <td><?= htmlspecialchars($s['size']) ?></td>

                    <td><?= htmlspecialchars($s['color']) ?></td>

                    <td><?= number_format($s['price'], 2) ?></td>

                    <td><?= $s['sold_qty'] ?></td>

                    <td><?= number_format($s['price'] * $s['sold_qty'], 2) ?></td>

                    <td>
                        <a href="invoice.php?sale_id=<?= $s['id'] ?>">
                            مشاهده
                        </a>
                    </td>
                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="9" style="text-align:center;">
                    هنوز فروشی ثبت نشده است
                </td>
            </tr>

        <?php endif; ?>

    </tbody>

</table>

<?php include "../includes/footer.php"; ?>