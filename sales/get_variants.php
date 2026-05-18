<?php
include "../includes/db.php";

$product_id = intval($_GET['product_id'] ?? 0);
$stmt = $db->prepare("
    SELECT v.id, c.name as color, v.size, v.quantity
    FROM product_variants v
    JOIN colors c ON v.color_id = c.id
    WHERE v.product_id = ?
    ORDER BY c.name ASC, v.size ASC
");
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($variants);
