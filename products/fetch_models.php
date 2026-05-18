<?php
include "../includes/db.php";

$q = trim($_GET['q'] ?? '');

if (!$q) {
    echo json_encode([]);
    exit;
}

$stmt = $db->prepare("SELECT DISTINCT model, price FROM products WHERE model LIKE ? ORDER BY model ASC");
$stmt->execute([$q . '%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
