<?php
include "../includes/db.php";
include "../includes/header.php";

// دریافت شناسه فروش
$sale_id = intval($_GET['sale_id'] ?? 0);

// دریافت اطلاعات فروش + کالا + مشخصات
$stmt = $db->prepare("
    SELECT 
        s.id, s.sale_date, s.quantity as sold_qty,
        p.model, p.price,
        v.size, c.name as color
    FROM sales s
    JOIN product_variants v ON s.variant_id = v.id
    JOIN products p ON v.product_id = p.id
    JOIN colors c ON v.color_id = c.id
    WHERE s.id = ?
");

$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die("فروش یافت نشد");
}

$total = $sale['price'] * $sale['sold_qty'];
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>فاکتور شماره <?= $sale['id'] ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            width: 80mm;
            direction: rtl;
        }

        h2, p {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table, th, td {
            border: 1px solid black;
            padding: 5px;
            text-align: center;
        }

        .total {
            font-weight: bold;
        }

        .print-btn {
            margin: 15px 0;
            text-align: center;
        }

        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>

<body>

<h2>فاکتور مدیریت انبار</h2>

<p>تاریخ: <?= htmlspecialchars($sale['sale_date']) ?></p>

<p>شماره فاکتور: <?= $sale['id'] ?></p>

<table>
    <tr>
        <th>کالا</th>
        <th>سایز</th>
        <th>رنگ</th>
        <th>قیمت</th>
        <th>تعداد</th>
        <th>جمع</th>
    </tr>

    <tr>
        <td><?= htmlspecialchars($sale['model']) ?></td>
        <td><?= htmlspecialchars($sale['size']) ?></td>
        <td><?= htmlspecialchars($sale['color']) ?></td>
        <td><?= number_format($sale['price'], 2) ?></td>
        <td><?= $sale['sold_qty'] ?></td>
        <td><?= number_format($total, 2) ?></td>
    </tr>
</table>

<p class="total">
    مبلغ کل: <?= number_format($total, 2) ?>
</p>

<div class="print-btn">
    <button onclick="window.print()">
        چاپ فاکتور
    </button>
</div>

<?php include "../includes/footer.php"; ?>