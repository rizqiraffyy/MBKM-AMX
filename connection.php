<?php
$host = getenv(name: 'DB_HOST');
$port = getenv(name: 'DB_PORT');
$dbname = getenv(name: 'DB_NAME');
$username = getenv(name: 'DB_USER');
$password = getenv(name: 'DB_PASS');

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $conn = new PDO(dsn: $dsn, username: $username, password: $password);
    $conn->setAttribute(attribute: PDO::ATTR_ERRMODE, value: PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log(message: 'Database connection failed: ' . $e->getMessage());
    die("Koneksi database gagal.");
}
?>