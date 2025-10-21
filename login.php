<?php
session_start(); // Start session
require 'connection.php';

try {
    // Ambil data dari form
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Siapkan SQL statement untuk menghindari SQL Injection
    $query_sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $conn->prepare($query_sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    // Periksa apakah email ditemukan
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(); // Ambil data pengguna

        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Set session variables dan redirect ke dashboard
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['fullname'] = $user['fullname'];
            header("Location: dashboard.php");
            exit; // Pastikan kode berhenti setelah redirect
        } else {
            // Password salah
            echo "<center><h1>Email or Password Incorrect.</h1>
            <button><strong><a href='index.html'>Login</a></strong></button></center>";
        }
    } else {
        // Email tidak ditemukan
        echo "<center><h1>Email or Password Incorrect.</h1>
        <button><strong><a href='index.html'>Login</a></strong></button></center>";
    }
} catch (PDOException $e) {
    // Tangani error koneksi atau query
    echo "Error: " . $e->getMessage();
}
?>
