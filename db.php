<?php
// includes/db.php
// Database Configuration for XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // Default XAMPP password is empty
define('DB_NAME', 'charity_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:20px;background:#fff0f0;border:1px solid red;margin:20px;border-radius:8px;">
        <h3 style="color:red;">Database Connection Failed</h3>
        <p>' . $conn->connect_error . '</p>
        <p>Make sure MySQL is running in XAMPP and you have imported <strong>database.sql</strong>.</p>
    </div>');
}
$conn->set_charset("utf8");

// Helper: auto-generate next ID for a table
function nextID($conn, $table, $col, $prefix, $digits = 3) {
    $res = $conn->query("SELECT MAX(CAST(SUBSTRING($col, " . (strlen($prefix)+1) . ") AS UNSIGNED)) AS mx FROM $table");
    $row = $res->fetch_assoc();
    $num = ($row['mx'] ?? 0) + 1;
    return $prefix . str_pad($num, $digits, '0', STR_PAD_LEFT);
}
?>
