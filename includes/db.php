<?php
// -----------------------------
// Database Connection
// -----------------------------

// --- SQLite ---
try {
    $db = new PDO('sqlite:' . __DIR__ . '/shoeshop.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables if not exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS colors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) UNIQUE NOT NULL
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            model VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            image VARCHAR(255) DEFAULT NULL
        );

        CREATE TABLE IF NOT EXISTS product_variants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            color_id INTEGER NOT NULL,
            size VARCHAR(10) NOT NULL,
            quantity INTEGER NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY(color_id) REFERENCES colors(id)
        );

        CREATE TABLE IF NOT EXISTS sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            variant_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            sale_date DATETIME NOT NULL,
            FOREIGN KEY(variant_id) REFERENCES product_variants(id)
        );
    ");

} catch (PDOException $e) {
    die("DB Error (SQLite): " . $e->getMessage());
}

// --- MySQL (اختیاری) ---
// Uncomment if you want MySQL instead of SQLite
/*
try {
    $db = new PDO('mysql:host=localhost;dbname=shoeshop;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("
        CREATE TABLE IF NOT EXISTS colors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) UNIQUE NOT NULL
        );

        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            model VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            image VARCHAR(255) DEFAULT NULL
        );

        CREATE TABLE IF NOT EXISTS product_variants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            color_id INT NOT NULL,
            size VARCHAR(10) NOT NULL,
            quantity INT NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY(color_id) REFERENCES colors(id)
        );

        CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            variant_id INT NOT NULL,
            quantity INT NOT NULL,
            sale_date DATETIME NOT NULL,
            FOREIGN KEY(variant_id) REFERENCES product_variants(id)
        );
    ");

} catch (PDOException $e) {
    die("DB Error (MySQL): " . $e->getMessage());
}
*/
?>
