<?php
$base = '/warehouse';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سیستم مدیریت انبار</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            direction: rtl;
            text-align: right;
        }

        header {
            background: #007BFF;
            color: white;
            padding: 15px 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }

        nav {
            background: #0056b3;
            padding: 10px 0;
            text-align: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            padding: 20px;
        }
    </style>
</head>
<body>

<header>
    سیستم مدیریت انبار
</header>

<nav>
    <a href="<?= $base ?>/index.php">داشبورد</a>
    <a href="<?= $base ?>/products/list.php">کالاها</a>
    <a href="<?= $base ?>/sales/list.php">ورود و خروج</a>
    <a href="<?= $base ?>/reports/history.php">گزارش‌ها</a>
</nav>

<main>