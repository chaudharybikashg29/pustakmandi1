<?php
/* ==========================================
   PUSTAKMANDI — config/db.php
   PDO connection, shared by every page
========================================== */

$DB_HOST = 'localhost';
$DB_NAME = 'pustakmandi';
$DB_USER = 'root';
$DB_PASS = '';   // set this if your MySQL root user has a password

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}