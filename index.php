<?php
include "includes/db.php";
require_once "includes/jdf.php"; // Jalali date functions

// Summary counts
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalVariants = $db->query("SELECT COUNT(*) FROM product_variants")->fetchColumn();
$totalStock = $db->query("SELECT IFNULL(SUM(quantity),0) FROM product_variants")->fetchColumn();

// Today's sales
$todaySales = $db->query("
    SELECT IFNULL(SUM(p.price * s.quantity), 0)
    FROM sales s
    JOIN product_variants v ON s.variant_id = v.id
    JOIN products p ON v.product_id = p.id
    WHERE DATE(s.sale_date) = DATE('now')
")->fetchColumn();

// This month's sales
$monthlySales = $db->query("
    SELECT IFNULL(SUM(p.price * s.quantity), 0)
    FROM sales s
    JOIN product_variants v ON s.variant_id = v.id
    JOIN products p ON v.product_id = p.id
    WHERE strftime('%Y-%m', s.sale_date) = strftime('%Y-%m', 'now')
")->fetchColumn();

// Daily sales
$dailySalesRaw = $db->query("
    SELECT DATE(s.sale_date) as day, SUM(p.price * s.quantity) as total
    FROM sales s
    JOIN product_variants v ON s.variant_id = v.id
    JOIN products p ON v.product_id = p.id
    WHERE strftime('%Y-%m', s.sale_date) = strftime('%Y-%m', 'now')
    GROUP BY DATE(s.sale_date)
    ORDER BY day ASC
")->fetchAll(PDO::FETCH_ASSOC);

$dailySales = [];
$dailyLabels = [];
foreach ($dailySalesRaw as $row) {
    $dailySales[] = $row['total'];
    $dailyLabels[] = jdate("Y/m/d", strtotime($row['day']));
}

// Hourly sales
$hourlySalesRaw = $db->query("
    SELECT strftime('%H', s.sale_date) as hour, SUM(p.price * s.quantity) as total
    FROM sales s
    JOIN product_variants v ON s.variant_id = v.id
    JOIN products p ON v.product_id = p.id
    WHERE DATE(s.sale_date) = DATE('now')
    GROUP BY hour
    ORDER BY hour ASC
")->fetchAll(PDO::FETCH_ASSOC);

$hourlySales = [];
$hourLabels = [];

for ($i = 0; $i < 24; $i++) {
    $hourlySales[$i] = 0;
    $hourLabels[$i] = sprintf("%02d:00", $i);
}

foreach ($hourlySalesRaw as $row) {
    $hourlySales[intval($row['hour'])] = $row['total'];
}

include "includes/header.php";
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container my-5" dir="rtl">
    <h1 class="text-center mb-4">داشبورد مدیریت انبار</h1>

    <!-- Top summary -->
    <div class="row text-center mb-4 justify-content-center">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>تعداد کالاها</h5>
                    <h3><?= $totalProducts ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>تعداد تنوع کالا</h5>
                    <h3><?= $totalVariants ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>موجودی کل انبار</h5>
                    <h3><?= $totalStock ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom summary -->
    <div class="row text-center mb-5 justify-content-center">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>ثبت امروز</h5>
                    <h3><?= number_format($todaySales, 2) ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>ثبت این ماه</h5>
                    <h3><?= number_format($monthlySales, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="row text-center mb-5">
        <div class="col-md-2 mb-2"><a href="products/add.php" class="btn btn-primary w-100">➕ افزودن کالا</a></div>
        <div class="col-md-2 mb-2"><a href="products/list.php" class="btn btn-primary w-100">📦 لیست کالاها</a></div>
        <div class="col-md-2 mb-2"><a href="sales/add.php" class="btn btn-success w-100">💰 ثبت ورود/خروج</a></div>
        <div class="col-md-2 mb-2"><a href="sales/list.php" class="btn btn-success w-100">📑 لیست تراکنش‌ها</a></div>
        <div class="col-md-2 mb-2"><a href="reports/history.php" class="btn btn-warning w-100">📊 گزارش عملکرد</a></div>
        <div class="col-md-2 mb-2"><a href="reports/stock.php" class="btn btn-info w-100">📋 گزارش موجودی</a></div>
    </div>

    <!-- Monthly chart -->
    <div class="card shadow-sm p-4 mb-4">
        <h2 class="text-center mb-4">نمودار ماهانه (روزانه)</h2>
        <canvas id="monthlyChart" height="120"></canvas>
    </div>

    <!-- Hourly chart -->
    <div class="card shadow-sm p-4 mb-4">
        <h2 class="text-center mb-4">نمودار امروز (ساعتی)</h2>
        <canvas id="hourlyChart" height="120"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');

new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dailyLabels) ?>,
        datasets: [{
            label: 'ثبت روزانه',
            data: <?= json_encode($dailySales) ?>,
            borderColor: '#007BFF',
            backgroundColor: 'rgba(0,123,255,0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive:true,
        plugins:{legend:{display:true}},
        scales:{
            x:{title:{display:true,text:'تاریخ (شمسی)'}},
            y:{title:{display:true,text:'مقدار'}}
        }
    }
});

const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');

new Chart(hourlyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($hourLabels) ?>,
        datasets: [{
            label: 'ثبت ساعتی',
            data: <?= json_encode($hourlySales) ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive:true,
        plugins:{legend:{display:true}},
        scales:{
            x:{title:{display:true,text:'ساعت'}},
            y:{title:{display:true,text:'مقدار'}}
        }
    }
});
</script>

<?php include "includes/footer.php"; ?>