<?php
require 'connection.php';

try {
    // Ambil data dari form
    $fullname = $_POST["fullname"];
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Hash password sebelum menyimpan
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Persiapkan SQL statement untuk menghindari SQL Injection
    $query_sql = "INSERT INTO users (fullname, username, email, password) VALUES (:fullname, :username, :email, :password)";
    $stmt = $conn->prepare($query_sql);

    // Bind parameter ke SQL statement
    $stmt->bindParam(':fullname', $fullname);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);

    // Eksekusi statement
    if ($stmt->execute()) {
        header("Location: index.html"); // Redirect ke halaman index
    } else {
        echo "Sign Up Failed: " . implode(", ", $stmt->errorInfo());
    }
} catch (PDOException $e) {
    // Tangkap error jika terjadi
    echo "Error: " . $e->getMessage();
}

// Tidak perlu close connection secara eksplisit, PDO menutupnya secara otomatis
?>
